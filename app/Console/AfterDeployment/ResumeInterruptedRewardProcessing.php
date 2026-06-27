<?php

namespace App\Console\AfterDeployment;

use App\Game\BattleRewardProcessing\Services\BattleRewardResumeService;
use Illuminate\Console\Command;

class ResumeInterruptedRewardProcessing extends Command
{
    protected $signature = 'reward-processing:resume-interrupted
        {--apply : Apply repairs instead of dry-running}
        {--force : Kept for backward compatibility; --apply alone now scans all affected lanes}
        {--character_id= : Optional character id to scope recovery}';

    protected $description = 'Finds interrupted battle reward processing lanes and marks ledger-backed work resumable.';

    public function __construct(
        private readonly BattleRewardResumeService $battleRewardResumeService,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $apply = (bool) $this->option('apply');
        $characterIdOption = $this->option('character_id');
        $characterId = is_numeric($characterIdOption) ? (int) $characterIdOption : null;

        $mode = $apply ? 'apply' : 'dry-run';
        $this->line("[{$mode}] Scanning all interrupted reward processing lanes.");

        if (! is_null($characterId)) {
            $this->line("[{$mode}] Scoped to character_id={$characterId}.");
        }

        $summary = $this->battleRewardResumeService->resumeAll($apply, $characterId);

        if ($apply) {
            $this->line(
                "Applied resume: recovered {$summary['recovered_processing_request_count']} request(s)"
                .", pending-only wakes {$summary['pending_only_lane_wake_count']}"
                .", inactive lanes {$summary['inactive_queue_state_count']}"
                .", released locks {$summary['released_lock_count']}"
                .", recovery-blocked locked {$summary['locked_recovery_blocked_count']}"
                .", legacy failed {$summary['legacy_failed_processing_request_count']}"
                .", legacy skipped {$summary['legacy_skipped_processing_request_count']}"
                .", locked skipped {$summary['locked_skipped_count']}"
                .", restarted processors {$summary['restarted_processor_count']}"
                .", resumable steps {$summary['resumable_step_count']}.",
            );

            return;
        }

        $this->line(
            "Dry run: would recover {$summary['would_recover_processing_request_count']} request(s)"
            .", would wake pending-only lanes {$summary['would_pending_only_lane_wake_count']}"
            .", would mark inactive {$summary['would_inactive_queue_state_count']}"
            .", would release locks {$summary['would_release_lock_count']}"
            .", legacy failed {$summary['legacy_failed_processing_request_count']}"
            .", legacy skipped {$summary['legacy_skipped_processing_request_count']}"
            .", locked skipped {$summary['locked_skipped_count']}"
            .", recovery-blocked locked {$summary['locked_recovery_blocked_count']}.",
        );
    }
}
