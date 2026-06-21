<?php

namespace App\Console\Commands;

use App\Services\DeviceHierarchyAssignmentService;
use Illuminate\Console\Command;

class RepairDeviceHierarchyCustody extends Command
{
    protected $signature = 'devices:repair-hierarchy-custody';

    protected $description = 'Backfill missing regional manager / team leader assignment rows for unsold devices';

    public function handle(DeviceHierarchyAssignmentService $service): int
    {
        $counts = $service->repairMissingAssignments();

        $this->info('Hierarchy custody repair complete.');
        $this->line('Regional manager rows added/updated: '.$counts['regional_manager']);
        $this->line('Team leader rows added/updated: '.$counts['team_leader']);
        $this->line('Restored from approved returns: '.$counts['from_returns']);

        return self::SUCCESS;
    }
}
