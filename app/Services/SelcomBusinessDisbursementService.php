<?php

namespace App\Services;

use App\Jobs\DisburseCommissionLinesJob;
use App\Models\AgentCredit;
use App\Models\AgentSale;
use App\Models\Selcompay;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Sends agent commission OUT to the agent via the Selcom Business (disbursement)
 * API — real money leaves the platform wallet. This is the LIVE payout path for
 * the Admin → Pay out page.
 *
 * Distinct from {@see SelcomAgentCommissionCheckoutService}, which uses the Selcom
 * Checkout (collection/pull) product and is kept only as the dev/test flow.
 *
 * @see https://developer.selcom.business/
 */
class SelcomBusinessDisbursementService
{
    private const EPS = 0.0001;

    public function __construct(
        protected SelcomBusinessCredentialResolver $credentials,
        protected AgentCommissionExpenseService $expenses,
        protected WalletService $wallet,
    ) {
    }

    /**
     * Disburse the commission on a single credit/sale line to the agent's wallet.
     *
     * @return array{ok: bool, message: string, selcompay: ?Selcompay}
     */
    public function disburse(string $sourceType, int $sourceId): array
    {
        if (! Schema::hasTable('selcompays') || ! Schema::hasColumn('selcompays', 'purpose')) {
            return $this->fail('Database is not migrated for Selcom commission payouts (missing selcompays.purpose).');
        }

        if (! in_array($sourceType, ['credit', 'sale'], true)) {
            return $this->fail('Invalid payout source.');
        }

        if ($sourceType === 'credit') {
            $line = AgentCredit::query()->with('agent')->find($sourceId);
            $dbSourceType = 'agent_credit';
        } else {
            $line = AgentSale::query()->with('agent')->find($sourceId);
            $dbSourceType = 'agent_sale';
        }

        if (! $line) {
            return $this->fail('Commission line not found.');
        }

        $amount = (float) ($line->commission_paid ?? 0);
        if ($amount <= self::EPS) {
            return $this->fail('No commission amount to pay.');
        }

        /** @var User|null $agent */
        $agent = $line->agent;
        if (! $agent) {
            return $this->fail('Agent is missing for this line.');
        }

        $msisdn = $this->normalizeMsisdn((string) ($agent->phone ?? ''));
        if ($msisdn === null) {
            return $this->fail('Agent mobile number is missing or invalid (use 2557… / 2556…).');
        }

        $fiCode = $this->resolveFiCode($msisdn);
        if ($fiCode === null) {
            return $this->fail('This mobile network is not supported for disbursement.');
        }

        // Never pay the same line twice: block if a payout is pending or already completed.
        $existing = Selcompay::query()
            ->where('purpose', Selcompay::PURPOSE_AGENT_COMMISSION_DISBURSE)
            ->where('payout_source_type', $dbSourceType)
            ->where('payout_source_id', $line->id)
            ->whereIn('payment_status', ['pending', 'completed'])
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            if ($existing->payment_status === 'completed') {
                return $this->fail('This commission line already has a completed disbursement.');
            }

            return $this->fail('A disbursement is already pending for this line.');
        }

        // Payouts are funded from the vendor's pre-funded disbursement wallet.
        // Block (with a clear message) when the wallet cannot cover this commission.
        $tenantId = (int) ($line->tenant_id ?? \App\Support\TenantContext::id() ?? 0);
        if ($tenantId <= 0) {
            return $this->fail('Cannot determine the vendor for this commission line.');
        }

        if (! $this->wallet->hasSufficientBalance($tenantId, $amount)) {
            $balance = $this->wallet->balance($tenantId);

            return $this->fail(sprintf(
                'Insufficient wallet balance. Your disbursement wallet has %s TZS but this payout needs %s TZS. Please deposit funds into your wallet first.',
                number_format($balance, 0),
                number_format($amount, 0),
            ));
        }

        $creds = $this->credentials->resolve();
        if (trim($creds['api_key']) === '') {
            return $this->fail('Selcom Business is not configured (Superadmin → Platform settings → Selcom): missing API key.');
        }
        if (trim($creds['private_key_path']) === '' || ! is_file($creds['private_key_path']) || ! is_readable($creds['private_key_path'])) {
            return $this->fail('Selcom Business private key (.pem) is missing or unreadable on the server.');
        }

        $transId = 'DISB_' . strtoupper($sourceType) . '_' . $line->id . '_' . time();
        $amountInt = (int) round($amount);

        $payload = [
            'transId' => $transId,
            'recipientFiCode' => $fiCode,
            'recipientAccount' => $msisdn,
            'recipientName' => mb_substr(trim((string) ($agent->name ?: 'Agent')), 0, 100),
            'amount' => $amountInt,
            'purpose' => (string) config('selcom_business.purpose_code', 'BUSINESS_EXPENSES'),
            'remarks' => mb_substr('Agent commission ' . $sourceType . ' #' . $line->id, 0, 140),
        ];

        Log::info('Selcom Business disbursement: process', [
            'source' => $dbSourceType,
            'source_id' => $line->id,
            'transId' => $transId,
            'fiCode' => $fiCode,
            'amount' => $amountInt,
            'live' => $creds['live'],
        ]);

        $api = $this->makeApi($creds);
        $response = $api->processTransaction($payload);

        $state = $this->interpret($response);

        if ($state === 'failed') {
            $msg = $this->messageFrom($response) ?: 'Selcom Business rejected the disbursement.';
            Log::warning('Selcom Business disbursement failed', [
                'transId' => $transId,
                'response' => $response,
            ]);

            return $this->fail($msg);
        }

        try {
            $selcompay = Selcompay::create([
                'transid' => $transId,
                // Store Selcom's receipt/reference for traceability (status is queried by transId).
                'order_id' => $this->receiptFrom($response),
                'phone_number' => $msisdn,
                'amount' => $amountInt,
                'payment_status' => $state === 'completed' ? 'completed' : 'pending',
                'local_order_id' => null,
                'purpose' => Selcompay::PURPOSE_AGENT_COMMISSION_DISBURSE,
                'payout_source_type' => $dbSourceType,
                'payout_source_id' => $line->id,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // DB safety-net rejected a second COMPLETED payout for this line.
            if ($this->isDuplicateKey($e)) {
                return $this->fail('This commission line has already been disbursed.');
            }

            Log::error('Selcom Business disbursement: DB save failed', ['transId' => $transId, 'error' => $e->getMessage()]);

            return $this->fail('Could not save payout record. Please check the transaction status before retrying.');
        } catch (\Throwable $e) {
            Log::error('Selcom Business disbursement: DB save failed', ['transId' => $transId, 'error' => $e->getMessage()]);

            return $this->fail('Could not save payout record. Please check the transaction status before retrying.');
        }

        // Reserve the amount from the vendor wallet (idempotent per payout record).
        // Money has already left Selcom, so a rare balance race is logged, not fatal.
        try {
            $this->wallet->debit(
                $tenantId,
                (float) $amountInt,
                \App\Models\WalletTransaction::TYPE_PAYOUT,
                'selcompay',
                $selcompay->id,
                'Agent commission ' . $sourceType . ' #' . $line->id,
            );
        } catch (\Throwable $e) {
            Log::error('Wallet debit after disbursement failed', [
                'selcompay_id' => $selcompay->id,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }

        if ($selcompay->payment_status === 'completed') {
            $this->expenses->bookFromSelcompay($selcompay);

            return ['ok' => true, 'message' => 'Disbursement completed.', 'selcompay' => $selcompay];
        }

        return ['ok' => true, 'message' => 'Disbursement submitted; awaiting confirmation.', 'selcompay' => $selcompay];
    }

    /**
     * Poll Selcom for the current status of a pending disbursement, update the
     * record, and book the expense once completed.
     *
     * @return array{status: string, message: string}
     */
    public function refreshStatus(Selcompay $selcompay): array
    {
        if ($selcompay->purpose !== Selcompay::PURPOSE_AGENT_COMMISSION_DISBURSE) {
            return ['status' => 'error', 'message' => 'Not a disbursement record.'];
        }

        if ($selcompay->payment_status === 'completed') {
            $this->expenses->bookFromSelcompay($selcompay);

            return ['status' => 'completed', 'message' => 'Disbursement completed.'];
        }

        if (in_array($selcompay->payment_status, ['failed', 'timeout'], true)) {
            return ['status' => $selcompay->payment_status, 'message' => 'Disbursement ' . $selcompay->payment_status . '.'];
        }

        $creds = $this->credentials->resolve();
        if (trim($creds['api_key']) === '') {
            return ['status' => 'error', 'message' => 'Selcom Business is not configured.'];
        }

        try {
            $response = $this->makeApi($creds)->queryTransaction((string) $selcompay->transid);
        } catch (\Throwable $e) {
            Log::error('Selcom Business disbursement: query error', ['error' => $e->getMessage()]);

            return ['status' => 'error', 'message' => $e->getMessage()];
        }

        $state = $this->interpret($response);

        if ($state === 'completed') {
            try {
                $selcompay->update(['payment_status' => 'completed']);
            } catch (\Illuminate\Database\QueryException $e) {
                // Another row already settled this line (DB safety-net). Treat as done.
                if (! $this->isDuplicateKey($e)) {
                    throw $e;
                }
            }
            $selcompay->refresh();
            $this->expenses->bookFromSelcompay($selcompay);

            return ['status' => 'completed', 'message' => 'Disbursement completed.'];
        }

        if ($state === 'failed') {
            $selcompay->update(['payment_status' => 'failed']);
            $this->wallet->reversePayout((int) $selcompay->id, 'Reversal: disbursement failed');

            return ['status' => 'failed', 'message' => $this->messageFrom($response) ?: 'Disbursement failed.'];
        }

        // Give up waiting after 10 minutes; the record can be reconciled via query later.
        $createdAt = \Carbon\Carbon::parse($selcompay->created_at);
        if ($createdAt->diffInMinutes(now()) > 10) {
            $selcompay->update(['payment_status' => 'timeout']);
            $this->wallet->reversePayout((int) $selcompay->id, 'Reversal: disbursement timed out');

            return ['status' => 'timeout', 'message' => 'Timed out waiting for Selcom confirmation.'];
        }

        return ['status' => 'pending', 'message' => 'Still processing…'];
    }

    public function runCacheKey(int $tenantId): string
    {
        return 'selcom_disburse_run:'.$tenantId;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRunStatus(int $tenantId): ?array
    {
        $payload = Cache::get($this->runCacheKey($tenantId));

        return is_array($payload) ? $payload : null;
    }

    /**
     * Queue many commission lines for background Selcom Business disbursement.
     * Uses afterResponse so the browser is freed even when QUEUE_CONNECTION=sync.
     *
     * @param  list<array{type?: string, source?: string, id?: int, source_id?: int}>  $items
     * @return array{ok: bool, message: string, run_id: ?string, candidates: int, queued: bool}
     */
    public function queueDisburseLines(int $tenantId, array $items, ?int $userId = null): array
    {
        $normalized = $this->normalizeLineItems($items);
        if ($normalized === []) {
            return [
                'ok' => false,
                'message' => 'No eligible commission lines to disburse.',
                'run_id' => null,
                'candidates' => 0,
                'queued' => false,
            ];
        }

        $existing = $this->getRunStatus($tenantId);
        if ($existing && in_array($existing['status'] ?? '', ['queued', 'running'], true)) {
            return [
                'ok' => false,
                'message' => 'A disbursement run is already in progress for this vendor. Wait for it to finish, then try again.',
                'run_id' => $existing['run_id'] ?? null,
                'candidates' => (int) ($existing['candidates'] ?? 0),
                'queued' => false,
            ];
        }

        $runId = (string) Str::uuid();
        $this->putRunStatus($tenantId, [
            'run_id' => $runId,
            'status' => 'queued',
            'candidates' => count($normalized),
            'processed' => 0,
            'started' => 0,
            'skipped' => 0,
            'failures' => [],
            'stopped_early' => null,
            'message' => 'Queued '.count($normalized).' commission line(s) for Selcom disbursement.',
            'updated_at' => now()->toIso8601String(),
        ]);

        DisburseCommissionLinesJob::dispatch($tenantId, $normalized, $runId, $userId)
            ->afterResponse();

        return [
            'ok' => true,
            'message' => 'Queued '.count($normalized).' commission line(s). Processing continues in the background — refresh this page to see progress.',
            'run_id' => $runId,
            'candidates' => count($normalized),
            'queued' => true,
        ];
    }

    /**
     * Collect every eligible credit/sale line (requires TenantContext).
     *
     * @return list<array{type: string, id: int}>
     */
    public function collectEligibleLineItems(): array
    {
        if (! Schema::hasTable('selcompays') || ! Schema::hasColumn('selcompays', 'purpose')) {
            return [];
        }

        $items = collect();

        foreach (AgentCredit::query()->where('commission_paid', '>', self::EPS)->orderByDesc('id')->get() as $c) {
            $items->push([
                'type' => 'credit',
                'id' => (int) $c->id,
                'sort' => $c->date?->timestamp ?? $c->created_at->timestamp,
            ]);
        }

        foreach (AgentSale::query()->where('commission_paid', '>', self::EPS)->orderByDesc('id')->get() as $s) {
            $items->push([
                'type' => 'sale',
                'id' => (int) $s->id,
                'sort' => $s->date?->timestamp ?? $s->created_at->timestamp,
            ]);
        }

        return $items->sortByDesc('sort')->values()
            ->map(fn (array $row) => ['type' => $row['type'], 'id' => $row['id']])
            ->all();
    }

    /**
     * Disburse every eligible commission line (sync). Prefer {@see queueDisburseLines} for large batches.
     *
     * @return array{started: int, skipped: int, failures: array<int, string>, stopped_early: ?string, candidates: int}
     */
    public function bulkDisburseEligibleLines(): array
    {
        if (! Schema::hasTable('selcompays') || ! Schema::hasColumn('selcompays', 'purpose')) {
            return [
                'started' => 0,
                'skipped' => 0,
                'failures' => ['Run database migrations for Selcom commission payouts (selcompays.purpose).'],
                'stopped_early' => null,
                'candidates' => 0,
            ];
        }

        return $this->processDisburseLines($this->collectEligibleLineItems());
    }

    /**
     * @param  list<array{type: string, id: int}>  $items
     * @param  (callable(array{started: int, skipped: int, failures: array<int, string>, stopped_early: ?string, candidates: int, processed: int}): void)|null  $onProgress
     * @return array{started: int, skipped: int, failures: array<int, string>, stopped_early: ?string, candidates: int, processed: int}
     */
    public function processDisburseLines(array $items, ?callable $onProgress = null): array
    {
        $started = 0;
        $skipped = 0;
        $failures = [];
        $stoppedEarly = null;
        $candidates = count($items);
        $processed = 0;

        foreach ($items as $item) {
            $type = $item['type'] ?? '';
            $id = (int) ($item['id'] ?? 0);
            $processed++;

            if (! in_array($type, ['credit', 'sale'], true) || $id <= 0) {
                $skipped++;
                $this->emitProgress($onProgress, [
                    'started' => $started,
                    'skipped' => $skipped,
                    'failures' => $failures,
                    'stopped_early' => $stoppedEarly,
                    'candidates' => $candidates,
                    'processed' => $processed,
                ]);

                continue;
            }

            $result = $this->disburse($type, $id);

            if ($result['ok']) {
                $started++;
                usleep(350000);
                $this->emitProgress($onProgress, [
                    'started' => $started,
                    'skipped' => $skipped,
                    'failures' => $failures,
                    'stopped_early' => $stoppedEarly,
                    'candidates' => $candidates,
                    'processed' => $processed,
                ]);

                continue;
            }

            $msg = $result['message'];
            $lower = strtolower($msg);

            if (
                str_contains($lower, 'already pending')
                || str_contains($lower, 'already has a completed')
                || str_contains($lower, 'mobile number is missing')
                || str_contains($lower, 'not supported for disbursement')
                || str_contains($lower, 'agent is missing')
                || str_contains($lower, 'no commission amount')
                || str_contains($lower, 'commission line not found')
            ) {
                $skipped++;
                $this->emitProgress($onProgress, [
                    'started' => $started,
                    'skipped' => $skipped,
                    'failures' => $failures,
                    'stopped_early' => $stoppedEarly,
                    'candidates' => $candidates,
                    'processed' => $processed,
                ]);

                continue;
            }

            if (str_contains($lower, 'not configured') || str_contains($lower, 'not migrated') || str_contains($lower, 'private key')) {
                $stoppedEarly = $msg;
                $failures[] = $msg;
                $this->emitProgress($onProgress, [
                    'started' => $started,
                    'skipped' => $skipped,
                    'failures' => $failures,
                    'stopped_early' => $stoppedEarly,
                    'candidates' => $candidates,
                    'processed' => $processed,
                ]);
                break;
            }

            $label = $type === 'credit' ? 'Credit #'.$id : 'Sale #'.$id;
            $failures[] = $label.': '.$msg;
            $this->emitProgress($onProgress, [
                'started' => $started,
                'skipped' => $skipped,
                'failures' => $failures,
                'stopped_early' => $stoppedEarly,
                'candidates' => $candidates,
                'processed' => $processed,
            ]);
        }

        return [
            'started' => $started,
            'skipped' => $skipped,
            'failures' => $failures,
            'stopped_early' => $stoppedEarly,
            'candidates' => $candidates,
            'processed' => $processed,
        ];
    }

    public function markRunRunning(int $tenantId, string $runId, int $candidates): void
    {
        $this->putRunStatus($tenantId, array_merge($this->getRunStatus($tenantId) ?? [], [
            'run_id' => $runId,
            'status' => 'running',
            'candidates' => $candidates,
            'message' => 'Disbursing commission lines via Selcom…',
            'updated_at' => now()->toIso8601String(),
        ]));
    }

    /**
     * @param  array{started?: int, skipped?: int, failures?: array<int, string>, stopped_early?: ?string, candidates?: int, processed?: int}  $partial
     */
    public function markRunProgress(int $tenantId, string $runId, array $partial): void
    {
        $this->putRunStatus($tenantId, array_merge($this->getRunStatus($tenantId) ?? [], [
            'run_id' => $runId,
            'status' => 'running',
            'candidates' => (int) ($partial['candidates'] ?? 0),
            'processed' => (int) ($partial['processed'] ?? 0),
            'started' => (int) ($partial['started'] ?? 0),
            'skipped' => (int) ($partial['skipped'] ?? 0),
            'failures' => $partial['failures'] ?? [],
            'stopped_early' => $partial['stopped_early'] ?? null,
            'message' => sprintf(
                'Processed %d of %d line(s)…',
                (int) ($partial['processed'] ?? 0),
                (int) ($partial['candidates'] ?? 0),
            ),
            'updated_at' => now()->toIso8601String(),
        ]));
    }

    /**
     * @param  array{started: int, skipped: int, failures: array<int, string>, stopped_early: ?string, candidates: int, processed?: int}  $summary
     */
    public function markRunFinished(int $tenantId, string $runId, array $summary): void
    {
        $this->putRunStatus($tenantId, [
            'run_id' => $runId,
            'status' => 'completed',
            'candidates' => (int) ($summary['candidates'] ?? 0),
            'processed' => (int) ($summary['processed'] ?? $summary['candidates'] ?? 0),
            'started' => (int) ($summary['started'] ?? 0),
            'skipped' => (int) ($summary['skipped'] ?? 0),
            'failures' => $summary['failures'] ?? [],
            'stopped_early' => $summary['stopped_early'] ?? null,
            'message' => sprintf(
                'Finished: %d submitted, %d skipped of %d line(s).',
                (int) ($summary['started'] ?? 0),
                (int) ($summary['skipped'] ?? 0),
                (int) ($summary['candidates'] ?? 0),
            ),
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    public function markRunFailed(int $tenantId, string $runId, string $message): void
    {
        $existing = $this->getRunStatus($tenantId) ?? [];

        $this->putRunStatus($tenantId, array_merge($existing, [
            'run_id' => $runId,
            'status' => 'failed',
            'message' => $message,
            'failures' => array_values(array_unique(array_merge(
                $existing['failures'] ?? [],
                [$message],
            ))),
            'updated_at' => now()->toIso8601String(),
        ]));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function putRunStatus(int $tenantId, array $payload): void
    {
        Cache::put($this->runCacheKey($tenantId), $payload, now()->addHours(6));
    }

    /**
     * @param  list<array{type?: string, source?: string, id?: int, source_id?: int}>  $items
     * @return list<array{type: string, id: int}>
     */
    protected function normalizeLineItems(array $items): array
    {
        $out = [];
        foreach ($items as $item) {
            $type = $item['type'] ?? $item['source'] ?? '';
            $id = (int) ($item['id'] ?? $item['source_id'] ?? 0);
            if (! in_array($type, ['credit', 'sale'], true) || $id <= 0) {
                continue;
            }
            $out[] = ['type' => $type, 'id' => $id];
        }

        return $out;
    }

    /**
     * @param  (callable(array<string, mixed>): void)|null  $onProgress
     * @param  array<string, mixed>  $partial
     */
    protected function emitProgress(?callable $onProgress, array $partial): void
    {
        if ($onProgress) {
            $onProgress($partial);
        }
    }

    protected function makeApi(array $creds): SelcomBusinessApiService
    {
        return new SelcomBusinessApiService(
            $creds['api_key'],
            $creds['private_key_path'],
            $creds['live'],
        );
    }

    /**
     * Map a Selcom Business API response to one of: completed | pending | failed.
     */
    protected function interpret(array $response): string
    {
        $resultcode = isset($response['resultcode']) ? (string) $response['resultcode'] : null;
        $status = strtoupper((string) ($response['data']['status'] ?? ''));
        $success = $response['success'] ?? null;
        $result = strtoupper((string) ($response['result'] ?? ''));

        if ($resultcode === '000' || $status === 'COMPLETED' || $result === 'SUCCESS') {
            return 'completed';
        }

        if ($resultcode === '111' || $status === 'ACCEPTED' || $result === 'INPROGRESS' || $result === 'PENDING') {
            return 'pending';
        }

        if ($success === false || $result === 'FAIL' || $status === 'FAILED') {
            return 'failed';
        }

        // Unknown/ambiguous shape: treat as pending so it is reconciled by query,
        // never silently booked as paid.
        return 'pending';
    }

    protected function messageFrom(array $response): string
    {
        return trim((string) ($response['message'] ?? $response['data']['message'] ?? $response['result'] ?? ''));
    }

    protected function receiptFrom(array $response): ?string
    {
        $receipt = $response['data']['selcom_receipt'] ?? $response['data']['reference'] ?? $response['reference'] ?? null;

        return $receipt !== null ? (string) $receipt : null;
    }

    protected function isDuplicateKey(\Illuminate\Database\QueryException $e): bool
    {
        return ($e->errorInfo[1] ?? null) === 1062;
    }

    protected function resolveFiCode(string $msisdn): ?string
    {
        return SelcomBusinessApiService::fiCodeForMsisdn($msisdn);
    }

    protected function normalizeMsisdn(string $raw): ?string
    {
        try {
            return \App\Support\TanzaniaMobileNumber::normalize($raw);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @return array{ok: false, message: string, selcompay: null}
     */
    protected function fail(string $message): array
    {
        return ['ok' => false, 'message' => $message, 'selcompay' => null];
    }
}
