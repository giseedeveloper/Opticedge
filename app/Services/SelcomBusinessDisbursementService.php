<?php

namespace App\Services;

use App\Models\AgentCredit;
use App\Models\AgentSale;
use App\Models\Selcompay;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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

            return ['status' => 'failed', 'message' => $this->messageFrom($response) ?: 'Disbursement failed.'];
        }

        // Give up waiting after 10 minutes; the record can be reconciled via query later.
        $createdAt = \Carbon\Carbon::parse($selcompay->created_at);
        if ($createdAt->diffInMinutes(now()) > 10) {
            $selcompay->update(['payment_status' => 'timeout']);

            return ['status' => 'timeout', 'message' => 'Timed out waiting for Selcom confirmation.'];
        }

        return ['status' => 'pending', 'message' => 'Still processing…'];
    }

    /**
     * Disburse every eligible commission line in one action.
     *
     * @return array{started: int, skipped: int, failures: array<int, string>, stopped_early: ?string, candidates: int}
     */
    public function bulkDisburseEligibleLines(): array
    {
        $started = 0;
        $skipped = 0;
        $failures = [];
        $stoppedEarly = null;

        if (! Schema::hasTable('selcompays') || ! Schema::hasColumn('selcompays', 'purpose')) {
            return [
                'started' => 0,
                'skipped' => 0,
                'failures' => ['Run database migrations for Selcom commission payouts (selcompays.purpose).'],
                'stopped_early' => null,
                'candidates' => 0,
            ];
        }

        $items = collect();

        foreach (AgentCredit::query()->where('commission_paid', '>', self::EPS)->orderByDesc('id')->get() as $c) {
            $items->push(['type' => 'credit', 'id' => $c->id, 'sort' => $c->date?->timestamp ?? $c->created_at->timestamp]);
        }

        foreach (AgentSale::query()->where('commission_paid', '>', self::EPS)->orderByDesc('id')->get() as $s) {
            $items->push(['type' => 'sale', 'id' => $s->id, 'sort' => $s->date?->timestamp ?? $s->created_at->timestamp]);
        }

        $items = $items->sortByDesc('sort')->values();
        $candidates = $items->count();

        foreach ($items as $item) {
            $result = $this->disburse($item['type'], $item['id']);

            if ($result['ok']) {
                $started++;
                usleep(350000);

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

                continue;
            }

            if (str_contains($lower, 'not configured') || str_contains($lower, 'not migrated') || str_contains($lower, 'private key')) {
                $stoppedEarly = $msg;
                $failures[] = $msg;
                break;
            }

            $label = $item['type'] === 'credit' ? 'Credit #' . $item['id'] : 'Sale #' . $item['id'];
            $failures[] = $label . ': ' . $msg;
        }

        return [
            'started' => $started,
            'skipped' => $skipped,
            'failures' => $failures,
            'stopped_early' => $stoppedEarly,
            'candidates' => $candidates,
        ];
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
