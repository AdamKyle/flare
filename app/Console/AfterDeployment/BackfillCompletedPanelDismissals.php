<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\DelveExploration;
use App\Flare\Models\ExplorationLog;
use Illuminate\Console\Command;

class BackfillCompletedPanelDismissals extends Command
{
    protected $signature = 'backfill:completed-panel-dismissals {--apply : Apply changes instead of dry-running}';

    protected $description = 'Backfills panel_dismissed_at for old ended ExplorationLog and completed DelveExploration rows that pre-date the soft-dismiss feature.';

    public function handle(): void
    {
        $mode = $this->option('apply') ? 'apply' : 'dry-run';

        $explorationCount = ExplorationLog::whereNotNull('ended_at')
            ->whereNull('panel_dismissed_at')
            ->count();

        $delveCount = DelveExploration::whereNotNull('completed_at')
            ->whereNull('panel_dismissed_at')
            ->count();

        $this->line("[{$mode}] ExplorationLog rows to backfill: {$explorationCount}");
        $this->line("[{$mode}] DelveExploration rows to backfill: {$delveCount}");

        if ($explorationCount === 0 && $delveCount === 0) {
            $this->line('Nothing to backfill.');

            return;
        }

        if (! $this->option('apply')) {
            $this->line('Run with --apply to apply changes.');

            return;
        }

        ExplorationLog::whereNotNull('ended_at')
            ->whereNull('panel_dismissed_at')
            ->update(['panel_dismissed_at' => now()]);

        DelveExploration::whereNotNull('completed_at')
            ->whereNull('panel_dismissed_at')
            ->update(['panel_dismissed_at' => now()]);

        $this->line("Done: backfilled {$explorationCount} exploration log(s) and {$delveCount} delve exploration(s).");
    }
}
