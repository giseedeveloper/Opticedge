<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentCredit;
use App\Models\AgentSale;
use App\Models\Selcompay;
use App\Services\AgentCommissionExpenseService;
use App\Services\DefaultAgentCommissionService;
use App\Services\SelcomBusinessDisbursementService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PayoutController extends Controller
{
    private const EPS = 0.0001;

    public function __construct(
        protected WalletService $wallet,
        protected DefaultAgentCommissionService $defaultCommission,
    ) {
    }

    /**
     * Pay out hub: date tabs of per-agent commission totals.
     */
    public function index(): View
    {
        $groups = $this->buildAgentDayGroups();
        $dateTabs = $groups
            ->groupBy('date')
            ->map(function ($dayGroups, $date) {
                return [
                    'date' => $date,
                    'label' => Carbon::parse($date)->format('D, M j, Y'),
                    'agents' => $dayGroups->values(),
                    'total_commission' => round($dayGroups->sum('commission_amount'), 2),
                    'total_devices' => (int) $dayGroups->sum('devices'),
                ];
            })
            ->sortKeysDesc()
            ->values();

        $totals = [
            'agents' => $groups->count(),
            'devices' => (int) $groups->sum('devices'),
            'commission' => round($groups->sum('commission_amount'), 2),
            'booked' => round($groups->where('payout_booked', true)->sum('commission_amount'), 2),
            'pending' => round($groups->where('payout_booked', false)->sum('commission_amount'), 2),
        ];

        $bulkEligibleCount = $groups->filter(fn (array $g) => ($g['can_pay'] ?? false) === true)->count();

        $tenantId = (int) (auth()->user()->tenant_id ?? \App\Support\TenantContext::id() ?? 0);
        $walletBalance = $tenantId > 0 ? $this->wallet->balance($tenantId) : 0.0;
        $defaultCommissionAmount = $this->defaultCommission->getAmount($tenantId > 0 ? $tenantId : null);
        $activeDate = request()->query('date')
            ?: ($dateTabs->first()['date'] ?? null);
        $disburseRun = $tenantId > 0
            ? app(SelcomBusinessDisbursementService::class)->getRunStatus($tenantId)
            : null;

        return view('admin.payout.index', [
            'dateTabs' => $dateTabs,
            'activeDate' => $activeDate,
            'totals' => $totals,
            'bulkEligibleCount' => $bulkEligibleCount,
            'walletBalance' => $walletBalance,
            'defaultCommissionAmount' => $defaultCommissionAmount,
            'disburseRun' => $disburseRun,
        ]);
    }

    public function updateDefaultCommission(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'default_commission_amount' => 'required|numeric|min:0',
        ]);

        $this->defaultCommission->setAmount((float) $validated['default_commission_amount']);

        return redirect()
            ->route('admin.payout.index')
            ->with('success', 'Default commission per sale updated. New cash and credit sales will use this amount automatically.');
    }

    /**
     * Edit total commission for one agent on one date (before disbursement only).
     */
    public function updateGroupCommission(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'agent_id' => 'required|integer|min:1',
            'commission_amount' => 'required|numeric|min:0',
        ]);

        $date = Carbon::parse($validated['date'])->toDateString();
        $agentId = (int) $validated['agent_id'];
        $newTotal = (float) $validated['commission_amount'];

        $group = $this->buildAgentDayGroups()
            ->first(fn (array $g) => $g['date'] === $date && (int) $g['agent_id'] === $agentId);

        if (! $group) {
            return redirect()
                ->route('admin.payout.index', ['date' => $date])
                ->withErrors(['selcom' => 'Commission group not found for that agent and date.']);
        }

        if (! empty($group['disburse_completed']) || ! empty($group['edit_locked'])) {
            return redirect()
                ->route('admin.payout.index', ['date' => $date])
                ->withErrors(['selcom' => 'This commission has already been disbursed and cannot be edited.']);
        }

        $lines = $group['lines'] ?? [];
        if ($lines === []) {
            return redirect()
                ->route('admin.payout.index', ['date' => $date])
                ->withErrors(['selcom' => 'No sale lines found for this group.']);
        }

        $deviceCount = max(1, (int) ($group['devices'] ?? count($lines)));
        $perDevice = round($newTotal / $deviceCount, 2);

        try {
            DB::transaction(function () use ($lines, $perDevice, $newTotal, $deviceCount) {
                $commissionService = app(AgentCommissionExpenseService::class);
                $assigned = 0.0;
                $lastIndex = count($lines) - 1;

                foreach ($lines as $i => $line) {
                    $qty = max(1, (int) ($line['quantity'] ?? 1));
                    // Put remainder on the last line so the sum matches exactly.
                    if ($i === $lastIndex) {
                        $lineAmount = round($newTotal - $assigned, 2);
                    } else {
                        $lineAmount = round($perDevice * $qty, 2);
                        $assigned += $lineAmount;
                    }

                    if ($line['source'] === 'credit') {
                        $model = AgentCredit::query()->lockForUpdate()->find($line['source_id']);
                        if (! $model) {
                            continue;
                        }
                        if (app(DefaultAgentCommissionService::class)->lineIsDisbursed('credit', $model->id)) {
                            throw new \RuntimeException('A line in this group was disbursed; edit aborted.');
                        }
                        $this->applyCommissionToCredit($model, $lineAmount, $commissionService);
                    } else {
                        $model = AgentSale::query()->lockForUpdate()->find($line['source_id']);
                        if (! $model) {
                            continue;
                        }
                        if (app(DefaultAgentCommissionService::class)->lineIsDisbursed('sale', $model->id)) {
                            throw new \RuntimeException('A line in this group was disbursed; edit aborted.');
                        }
                        $this->applyCommissionToSale($model, $lineAmount, $commissionService);
                    }
                }
            });
        } catch (\Throwable $e) {
            Log::error('Group commission edit failed: '.$e->getMessage(), ['exception' => $e]);

            return redirect()
                ->route('admin.payout.index', ['date' => $date])
                ->withErrors(['selcom' => $e->getMessage() ?: 'Could not update commission.']);
        }

        return redirect()
            ->route('admin.payout.index', ['date' => $date])
            ->with('success', 'Commission updated for '.$group['agent_name'].' on '.$date.'.');
    }

    /**
     * Queue disbursement for all eligible lines for one agent on one date.
     */
    public function payGroup(Request $request, SelcomBusinessDisbursementService $disburse): RedirectResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'agent_id' => 'required|integer|min:1',
        ]);

        $date = Carbon::parse($validated['date'])->toDateString();
        $agentId = (int) $validated['agent_id'];

        $group = $this->buildAgentDayGroups()
            ->first(fn (array $g) => $g['date'] === $date && (int) $g['agent_id'] === $agentId);

        if (! $group) {
            return redirect()
                ->route('admin.payout.index', ['date' => $date])
                ->withErrors(['selcom' => 'Commission group not found.']);
        }

        if (! empty($group['disburse_completed'])) {
            return redirect()
                ->route('admin.payout.index', ['date' => $date])
                ->withErrors(['selcom' => 'This commission has already been disbursed.']);
        }

        $tenantId = (int) (auth()->user()->tenant_id ?? \App\Support\TenantContext::id() ?? 0);
        if ($tenantId <= 0) {
            return redirect()
                ->route('admin.payout.index', ['date' => $date])
                ->withErrors(['selcom' => 'Your account is not linked to a vendor.']);
        }

        $items = [];
        foreach ($group['lines'] as $line) {
            if (! empty($line['disburse_completed']) || ! empty($line['pay_pending'])) {
                continue;
            }
            if (($line['commission_amount'] ?? 0) <= self::EPS) {
                continue;
            }
            $items[] = [
                'type' => $line['source'],
                'id' => (int) $line['source_id'],
            ];
        }

        $result = $disburse->queueDisburseLines($tenantId, $items, auth()->id());

        if (! $result['ok']) {
            return redirect()
                ->route('admin.payout.index', ['date' => $date])
                ->withErrors(['selcom' => $result['message']]);
        }

        return redirect()
            ->route('admin.payout.index', ['date' => $date])
            ->with('success', "Queued {$result['candidates']} disbursement(s) for {$group['agent_name']}. Processing continues in the background.")
            ->with('bulk_selcom_summary', [
                'queued' => true,
                'candidates' => $result['candidates'],
                'started' => 0,
                'skipped' => 0,
                'failures' => [],
                'message' => $result['message'],
            ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    protected function buildAgentDayGroups()
    {
        $completedSelcomKeys = $this->completedDisburseKeys();
        $latestSelcom = $this->latestDisburseBySource();

        $raw = collect();

        $creditQuery = AgentCredit::query()
            ->with(['agent:id,name,phone', 'teamLeader:id,name,phone'])
            ->where('commission_paid', '>', self::EPS)
            ->orderByDesc('id');

        foreach ($creditQuery->get() as $c) {
            $agentId = (int) ($c->agent_id ?: 0);
            if ($agentId <= 0) {
                // Team-leader-only credits without an agent cannot be disbursed to an agent wallet.
                continue;
            }
            $date = $c->date
                ? Carbon::parse($c->date)->toDateString()
                : Carbon::parse($c->created_at)->toDateString();
            $key = ($date).'|agent:'.$agentId;
            $sourceKey = 'agent_credit:'.$c->id;
            $disbursed = isset($completedSelcomKeys[$sourceKey]);
            $sel = $latestSelcom->get($sourceKey);

            $raw->push([
                'group_key' => $key,
                'date' => $date,
                'agent_id' => $agentId,
                'agent_name' => $c->agent?->name ?? '—',
                'mobile' => $c->agent?->phone ?? '—',
                'line' => [
                    'source' => 'credit',
                    'source_id' => $c->id,
                    'quantity' => 1,
                    'commission_amount' => (float) $c->commission_paid,
                    'payout_booked' => $c->commission_expense_id !== null,
                    'disburse_completed' => $disbursed,
                    'pay_pending' => $sel && $sel->payment_status === 'pending',
                    'selcom' => $sel,
                ],
            ]);
        }

        $saleQuery = AgentSale::query()
            ->with(['agent:id,name,phone'])
            ->where('commission_paid', '>', self::EPS)
            ->orderByDesc('id');

        foreach ($saleQuery->get() as $s) {
            $agentId = (int) ($s->agent_id ?: 0);
            if ($agentId <= 0) {
                continue;
            }
            $date = $s->date
                ? Carbon::parse($s->date)->toDateString()
                : Carbon::parse($s->created_at)->toDateString();
            $key = ($date).'|agent:'.$agentId;
            $sourceKey = 'agent_sale:'.$s->id;
            $disbursed = isset($completedSelcomKeys[$sourceKey]);
            $sel = $latestSelcom->get($sourceKey);
            $qty = max(1, (int) ($s->quantity_sold ?? 1));

            $raw->push([
                'group_key' => $key,
                'date' => $date,
                'agent_id' => $agentId,
                'agent_name' => $s->agent?->name ?? ($s->seller_name ?: '—'),
                'mobile' => $s->agent?->phone ?? '—',
                'line' => [
                    'source' => 'sale',
                    'source_id' => $s->id,
                    'quantity' => $qty,
                    'commission_amount' => (float) $s->commission_paid,
                    'payout_booked' => $s->commission_expense_id !== null,
                    'disburse_completed' => $disbursed,
                    'pay_pending' => $sel && $sel->payment_status === 'pending',
                    'selcom' => $sel,
                ],
            ]);
        }

        return $raw->groupBy('group_key')->map(function ($items) {
            $first = $items->first();
            $lines = $items->pluck('line')->values()->all();
            $devices = (int) collect($lines)->sum('quantity');
            $commission = round(collect($lines)->sum('commission_amount'), 2);
            $allDisbursed = collect($lines)->every(fn ($l) => ! empty($l['disburse_completed']));
            $anyDisbursed = collect($lines)->contains(fn ($l) => ! empty($l['disburse_completed']));
            $anyPending = collect($lines)->contains(fn ($l) => ! empty($l['pay_pending']));
            $allBooked = collect($lines)->every(fn ($l) => ! empty($l['payout_booked']));
            $canPay = ! $allDisbursed && ! $anyPending && $commission > self::EPS
                && collect($lines)->contains(fn ($l) => empty($l['disburse_completed']) && empty($l['pay_pending']) && ($l['commission_amount'] ?? 0) > self::EPS);

            return [
                'group_key' => $first['group_key'],
                'date' => $first['date'],
                'agent_id' => $first['agent_id'],
                'agent_name' => $first['agent_name'],
                'mobile' => $first['mobile'],
                'devices' => $devices,
                'commission_amount' => $commission,
                'payout_booked' => $allBooked,
                'disburse_completed' => $allDisbursed,
                'any_disbursed' => $anyDisbursed,
                'edit_locked' => $anyDisbursed || $anyPending,
                'pay_pending' => $anyPending,
                'can_pay' => $canPay,
                'lines' => $lines,
            ];
        })->sortByDesc(fn (array $g) => $g['date'].'|'.$g['agent_name'])->values();
    }

    /**
     * @return array<string, true>
     */
    protected function completedDisburseKeys(): array
    {
        if (! Schema::hasTable('selcompays') || ! Schema::hasColumn('selcompays', 'purpose')) {
            return [];
        }

        $keys = Selcompay::query()
            ->where('purpose', Selcompay::PURPOSE_AGENT_COMMISSION_DISBURSE)
            ->where('payment_status', 'completed')
            ->whereNotNull('payout_source_type')
            ->whereNotNull('payout_source_id')
            ->get(['payout_source_type', 'payout_source_id'])
            ->map(fn (Selcompay $s) => $s->payout_source_type.':'.$s->payout_source_id)
            ->unique()
            ->values()
            ->all();

        return array_fill_keys($keys, true);
    }

    /**
     * @return \Illuminate\Support\Collection<string, Selcompay>
     */
    protected function latestDisburseBySource()
    {
        if (! Schema::hasTable('selcompays') || ! Schema::hasColumn('selcompays', 'purpose')) {
            return collect();
        }

        return Selcompay::query()
            ->where('purpose', Selcompay::PURPOSE_AGENT_COMMISSION_DISBURSE)
            ->whereNotNull('payout_source_type')
            ->whereNotNull('payout_source_id')
            ->orderByDesc('id')
            ->get()
            ->unique(fn (Selcompay $s) => $s->payout_source_type.':'.$s->payout_source_id)
            ->keyBy(fn (Selcompay $s) => $s->payout_source_type.':'.$s->payout_source_id);
    }

    protected function applyCommissionToSale(AgentSale $sale, float $newCommission, AgentCommissionExpenseService $commissionService): void
    {
        if ($newCommission <= self::EPS) {
            $commissionService->reverseForAgentSale($sale);
            $sale->update(['commission_paid' => $newCommission]);

            return;
        }

        $hasBookedExpense = Schema::hasColumn('agent_sales', 'commission_expense_id')
            && $sale->commission_expense_id;
        $amountChanged = abs((float) ($sale->commission_paid ?? 0) - $newCommission) > self::EPS;

        if ($hasBookedExpense && $amountChanged) {
            $commissionService->reverseForAgentSale($sale);
            $sale->refresh();
        }

        $sale->update(['commission_paid' => $newCommission]);
    }

    protected function applyCommissionToCredit(AgentCredit $credit, float $newCommission, AgentCommissionExpenseService $commissionService): void
    {
        if ($newCommission <= self::EPS) {
            $commissionService->reverseForAgentCredit($credit);
            $credit->update(['commission_paid' => $newCommission]);

            return;
        }

        $hasBookedExpense = Schema::hasColumn('agent_credits', 'commission_expense_id')
            && $credit->commission_expense_id;
        $amountChanged = abs((float) ($credit->commission_paid ?? 0) - $newCommission) > self::EPS;

        if ($hasBookedExpense && $amountChanged) {
            $commissionService->reverseForAgentCredit($credit);
            $credit->refresh();
        }

        $credit->update(['commission_paid' => $newCommission]);
    }
}
