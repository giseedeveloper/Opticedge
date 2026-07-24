<?php

namespace App\Jobs;

use App\Services\SelcomBusinessDisbursementService;
use App\Support\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Processes many Selcom Business commission disbursements in the background
 * so the admin HTTP request does not hit PHP / proxy timeouts.
 *
 * Unique per tenant so two bulk/group runs cannot overlap for the same vendor.
 */
class DisburseCommissionLinesJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Do not auto-retry money transfers; disburse() already blocks pending/completed duplicates. */
    public int $tries = 1;

    /** Allow large batches (Selcom + ~0.35s pacing per success). */
    public int $timeout = 900;

    /** Keep the unique lock long enough for a large run. */
    public int $uniqueFor = 960;

    /**
     * @param  list<array{type: string, id: int}>  $items
     */
    public function __construct(
        public int $tenantId,
        public array $items,
        public string $runId,
        public ?int $initiatedByUserId = null,
    ) {
    }

    public function uniqueId(): string
    {
        return 'disburse-commission-tenant-'.$this->tenantId;
    }

    public function handle(SelcomBusinessDisbursementService $disburse): void
    {
        TenantContext::set($this->tenantId);

        $disburse->markRunRunning($this->tenantId, $this->runId, count($this->items));

        try {
            $summary = $disburse->processDisburseLines($this->items, function (array $partial) use ($disburse): void {
                $disburse->markRunProgress($this->tenantId, $this->runId, $partial);
            });

            $disburse->markRunFinished($this->tenantId, $this->runId, $summary);

            Log::info('DisburseCommissionLinesJob finished', [
                'tenant_id' => $this->tenantId,
                'run_id' => $this->runId,
                'user_id' => $this->initiatedByUserId,
                'summary' => $summary,
            ]);
        } finally {
            TenantContext::clear();
        }
    }

    public function failed(?Throwable $exception): void
    {
        TenantContext::set($this->tenantId);

        try {
            app(SelcomBusinessDisbursementService::class)->markRunFailed(
                $this->tenantId,
                $this->runId,
                $exception?->getMessage() ?? 'Disbursement job failed.',
            );
        } finally {
            TenantContext::clear();
        }

        Log::error('DisburseCommissionLinesJob failed', [
            'tenant_id' => $this->tenantId,
            'run_id' => $this->runId,
            'user_id' => $this->initiatedByUserId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
