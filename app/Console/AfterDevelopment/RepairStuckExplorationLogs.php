<?php

namespace App\Console\AfterDevelopment;

use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\ExplorationLog;
use App\Flare\Models\ExplorationWarning;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RepairStuckExplorationLogs extends Command
{
    protected $signature = 'after-development:repair-stuck-exploration-logs {--apply : Apply repairs instead of dry-running}';

    protected $description = 'Scans ExplorationLog rows with ended_at null and no matching CharacterAutomation and repairs them.';

    public function handle(): void
    {
        $mode = $this->option('apply') ? 'apply' : 'dry-run';

        $openLogs = ExplorationLog::whereNull('ended_at')->get();

        $this->line('Open logs scanned: ' . $openLogs->count());

        if ($openLogs->isEmpty()) {
            $this->line('Logs repaired: 0');
            $this->line('Logs skipped: 0');

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

            Log::error('Stuck exploration log found with no matching automation.', [
                'command' => $this->getName(),
                'mode' => $mode,
                'exploration_log_id' => $log->id,
                'character_id' => $log->character_id,
                'character_automation_id' => $log->character_automation_id,
            ]);

            if (! $this->option('apply')) {
                $repaired++;
                continue;
            }

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

            $repaired++;
        }

        $this->line('Logs repaired: ' . $repaired);
        $this->line('Logs skipped: ' . $skipped);
    }
}
