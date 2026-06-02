<?php

namespace App\Services;

use App\Models\AgentCredit;
use App\Models\AgentSale;
use App\Models\Selcompay;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Initiates Selcom Checkout (create-order-minimal + wallet-payment) for an agent commission line.
 *
 * Selcom documents wallet-payment as a pull from the given MSISDN (buyer authorizes on phone).
 * Use only if your Selcom merchant setup matches this flow for commission settlement.
 */
class SelcomAgentCommissionCheckoutService
{
    public function __construct(
        protected SelcomCredentialResolver $credentials
    ) {
    }

    /**
     * @return array{ok: bool, message: string, selcompay: ?Selcompay}
     */
    public function initiate(string $sourceType, int $sourceId): array
    {
        if (! Schema::hasTable('selcompays') || ! Schema::hasColumn('selcompays', 'purpose')) {
            return ['ok' => false, 'message' => 'Database is not migrated for Selcom commission payouts (missing selcompays.purpose).', 'selcompay' => null];
        }

        $eps = 0.0001;

        if (! in_array($sourceType, ['credit', 'sale'], true)) {
            return ['ok' => false, 'message' => 'Invalid payout source.', 'selcompay' => null];
        }

        if ($sourceType === 'credit') {
            $line = AgentCredit::query()->with('agent')->find($sourceId);
            $dbSourceType = 'agent_credit';
        } else {
            $line = AgentSale::query()->with('agent')->find($sourceId);
            $dbSourceType = 'agent_sale';
        }

        if (! $line) {
            return ['ok' => false, 'message' => 'Commission line not found.', 'selcompay' => null];
        }

        $amount = (float) ($line->commission_paid ?? 0);
        if ($amount <= $eps) {
            return ['ok' => false, 'message' => 'No commission amount to pay.', 'selcompay' => null];
        }

        /** @var User|null $agent */
        $agent = $line->agent;
        if (! $agent) {
            return ['ok' => false, 'message' => 'Agent is missing for this line.', 'selcompay' => null];
        }

        $cleanPhone = $this->normalizeMsisdn((string) ($agent->phone ?? ''));
        if ($cleanPhone === null) {
            return ['ok' => false, 'message' => 'Agent mobile number is missing or invalid (use 2557… / 2556…).', 'selcompay' => null];
        }

        $open = Selcompay::query()
            ->where('purpose', Selcompay::PURPOSE_AGENT_COMMISSION_CHECKOUT)
            ->where('payout_source_type', $dbSourceType)
            ->where('payout_source_id', $line->id)
            ->where('payment_status', 'pending')
            ->whereNotNull('order_id')
            ->exists();

        if ($open) {
            return ['ok' => false, 'message' => 'A Selcom checkout is already pending for this line.', 'selcompay' => null];
        }

        $alreadyCompleted = Selcompay::query()
            ->where('purpose', Selcompay::PURPOSE_AGENT_COMMISSION_CHECKOUT)
            ->where('payout_source_type', $dbSourceType)
            ->where('payout_source_id', $line->id)
            ->where('payment_status', 'completed')
            ->exists();

        if ($alreadyCompleted) {
            return ['ok' => false, 'message' => 'This commission line already has a completed Selcom checkout.', 'selcompay' => null];
        }

        $creds = $this->credentials->resolve();
        if ($creds['vendor'] === '' || $creds['api_key'] === '' || $creds['api_secret'] === '') {
            return ['ok' => false, 'message' => 'Selcom is not configured (Admin → Store Settings).', 'selcompay' => null];
        }

        $selcom = new SelcomApiService(
            $creds['vendor'],
            $creds['api_key'],
            $creds['api_secret'],
            $creds['live']
        );

        $transid = 'COMM_' . strtoupper($sourceType) . '_' . $line->id . '_' . time();
        $orderId = 'COMM' . now()->timestamp . random_int(1000, 9999);

        $redirectUrl = route('admin.payout.index', [], true);
        $cancelUrl = route('admin.payout.index', [], true);
        $webhookUrl = route('selcom.checkout-callback');

        $buyerEmail = filter_var($agent->email, FILTER_VALIDATE_EMAIL)
            ? $agent->email
            : ('commission+' . $agent->id . '@' . (parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'invalid.local'));

        $createPayload = [
            'vendor' => $creds['vendor'],
            'order_id' => $orderId,
            'buyer_email' => $buyerEmail,
            'buyer_name' => $agent->name ?: 'Agent',
            'buyer_phone' => $cleanPhone,
            'amount' => (int) round($amount),
            'currency' => 'TZS',
            'redirect_url' => base64_encode($redirectUrl),
            'cancel_url' => base64_encode($cancelUrl),
            'webhook' => base64_encode($webhookUrl),
            'buyer_remarks' => 'Commission ' . $sourceType . ' #' . $line->id,
            'merchant_remarks' => '',
            'no_of_items' => 1,
            'expiry' => (int) (config('selcom.expiry') ?? 60),
        ];

        $colors = config('selcom.colors', []);
        if (! empty($colors['header'])) {
            $createPayload['header_colour'] = $colors['header'];
        }
        if (! empty($colors['link'])) {
            $createPayload['link_colour'] = $colors['link'];
        }
        if (! empty($colors['button'])) {
            $createPayload['button_colour'] = $colors['button'];
        }

        Log::info('Selcom commission checkout: create order', [
            'source' => $dbSourceType,
            'source_id' => $line->id,
            'transid' => $transid,
            'amount' => $createPayload['amount'],
        ]);

        $createResponse = $selcom->createOrderMinimal($createPayload);

        if (isset($createResponse['resultcode']) && $createResponse['resultcode'] !== '000') {
            $msg = $createResponse['message'] ?? $createResponse['result'] ?? 'Selcom create order failed';

            return ['ok' => false, 'message' => $msg, 'selcompay' => null];
        }

        $walletResponse = $selcom->walletPayment($transid, $orderId, $cleanPhone);

        Log::info('Selcom commission checkout: wallet payment', [
            'source' => $dbSourceType,
            'source_id' => $line->id,
            'response' => $walletResponse,
        ]);

        if (isset($walletResponse['resultcode']) && $walletResponse['resultcode'] !== '000' && $walletResponse['resultcode'] !== '111') {
            $msg = $walletResponse['message'] ?? $walletResponse['result'] ?? 'Selcom wallet payment failed';

            return ['ok' => false, 'message' => $msg, 'selcompay' => null];
        }

        try {
            $selcompay = Selcompay::create([
                'transid' => $transid,
                'order_id' => $orderId,
                'phone_number' => $cleanPhone,
                'amount' => $createPayload['amount'],
                'payment_status' => 'pending',
                'local_order_id' => null,
                'purpose' => Selcompay::PURPOSE_AGENT_COMMISSION_CHECKOUT,
                'payout_source_type' => $dbSourceType,
                'payout_source_id' => $line->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Selcom commission checkout: DB save failed', ['error' => $e->getMessage()]);

            return ['ok' => false, 'message' => 'Could not save payout record. Try again.', 'selcompay' => null];
        }

        return ['ok' => true, 'message' => 'Checkout started.', 'selcompay' => $selcompay];
    }

    /**
     * Start Selcom Checkout for every commission line that is eligible (same rules as {@see initiate}),
     * in one admin action. Each line is a separate Selcom order (separate USSD per line).
     *
     * @return array{started: int, skipped: int, failures: array<int, string>, stopped_early: ?string, candidates: int}
     */
    public function bulkInitiateEligibleLines(): array
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

        $eps = 0.0001;
        $items = collect();

        foreach (AgentCredit::query()->where('commission_paid', '>', $eps)->orderByDesc('id')->get() as $c) {
            $items->push([
                'type' => 'credit',
                'id' => $c->id,
                'sort' => $c->date?->timestamp ?? $c->created_at->timestamp,
            ]);
        }

        foreach (AgentSale::query()->where('commission_paid', '>', $eps)->orderByDesc('id')->get() as $s) {
            $items->push([
                'type' => 'sale',
                'id' => $s->id,
                'sort' => $s->date?->timestamp ?? $s->created_at->timestamp,
            ]);
        }

        $items = $items->sortByDesc('sort')->values();
        $candidates = $items->count();

        foreach ($items as $item) {
            $result = $this->initiate($item['type'], $item['id']);
            if ($result['ok']) {
                $started++;
                usleep(350000);

                continue;
            }

            $msg = $result['message'];
            $lower = strtolower($msg);
            if (
                str_contains($lower, 'already pending')
                || str_contains($lower, 'already has a completed selcom')
                || str_contains($lower, 'mobile number is missing')
                || str_contains($lower, 'invalid')
                || str_contains($lower, 'agent is missing')
                || str_contains($lower, 'no commission amount')
                || str_contains($lower, 'commission line not found')
            ) {
                $skipped++;

                continue;
            }

            if (str_contains($lower, 'not configured') || str_contains($lower, 'not migrated')) {
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

    protected function normalizeMsisdn(string $raw): ?string
    {
        try {
            return \App\Support\TanzaniaMobileNumber::normalize($raw);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }
}
