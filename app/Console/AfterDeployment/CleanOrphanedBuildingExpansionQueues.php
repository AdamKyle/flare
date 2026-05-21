<?php

namespace App\Console\AfterDeployment;

use App\Game\Kingdoms\Service\OrphanedBuildingExpansionQueueCleanupService;
use Illuminate\Console\Command;

class CleanOrphanedBuildingExpansionQueues extends Command
{
    protected $signature = 'clean:orphaned-building-expansion-queues';

    protected $description = 'Cleans up orphaned building expansion queues';

    public function handle(OrphanedBuildingExpansionQueueCleanupService $orphanedBuildingExpansionQueueCleanupService): void
    {
        $orphanedBuildingExpansionQueueCleanupService->clean();
    }
}
