<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\ExplorationLog;
use App\Flare\Models\ExplorationWarning;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RepairStuckExplorationLogs extends Command
{
    protected $signature = 'repair:stuck-exploration-logs {--apply : Apply repairs instead of dry-running}';

    protected $description = 'Finds ExplorationLog rows with ended_at null but no matching CharacterAutomation and repairs them.';

    public function handle(): void
    {
        $mode = $this->option('apply') ? 'apply' : 'dry-run';

        $openLogs = ExplorationLog::whereNull('ended_at')->get();

        if ($openLogs->isEmpty()) {
            $this->line('No open exploration logs found.');

            return;
        }

        $repaired = 0;
        $skipped = 0;

        foreach ($openLogs as $log) {
            $automation = CharacterAutomation::where('id', $log->character_automation_id)
                ->where('character_id', $log->character_id)
                ->first();

            if (! is_null($automation)) {
                $skipped++;

                continue;
            }

            if (! $this->option('apply')) {
                $this->line("[dry-run] Would repair exploration_log_id={$log->id} character_id={$log->character_id} automation_id={$log->character_automation_id}");
                $repaired++;

                continue;
            }

            Log::error('Stuck exploration log found with no matching automation.', [
                'command' => $this->getName(),
                'mode' => $mode,
                'character_id' => $log->character_id,
                'exploration_log_id' => $log->id,
                'character_automation_id' => $log->character_automation_id,
                'stopped_reason' => 'missing_automation',
                'apply' => $this->option('apply'),
            ]);

            $log->update([
                'ended_at' => now(),
                'stopped_reason' => 'missing_automation',
            ]);

            ExplorationWarning::create([
                'character_id' => $log->character_id,
                'user_id' => $log->user_id,
                'exploration_log_id' => $log->id,
                'type' => 'missing_automation',
                'message' => 'Exploration ended because the automation was missing. Please report this as a bug.',
            ]);

            $this->line("Repaired exploration_log_id={$log->id} character_id={$log->character_id}");
            $repaired++;
        }

        $mode = $this->option('apply') ? 'applied' : 'dry-run';
        $this->line("Done ({$mode}): skipped {$skipped} (active), repaired {$repaired} (missing automation).");
    }
}
