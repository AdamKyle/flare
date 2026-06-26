<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\CharacterBattleRewardQueueState;
use App\Game\BattleRewardProcessing\Services\BattleRewardProcessingQueueManager;
use App\Game\BattleRewardProcessing\Services\BattleRewardResumeService;
use Illuminate\Console\Command;

class ResumeInterruptedRewardProcessing extends Command
{
    protected $signature = 'reward-processing:resume-interrupted {--apply : Apply repairs instead of dry-running}';

    protected $description = 'Finds interrupted battle reward processing lanes and marks ledger-backed work resumable.';

    public function __construct(
        private readonly BattleRewardProcessingQueueManager $queueManager,
        private readonly BattleRewardResumeService $battleRewardResumeService,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $apply = (bool) $this->option('apply');
        $staleStates = CharacterBattleRewardQueueState::query()
            ->stale($this->queueManager->staleCutoff())
            ->with('character:id,name')
            ->orderBy('heartbeat_at')
            ->get();

        if ($staleStates->isEmpty()) {
            $this->line('No stale reward processing queues found.');

            return;
        }

        foreach ($staleStates as $state) {
            $mode = $apply ? 'apply' : 'dry-run';
            $this->line("[{$mode}] would-resume character_id={$state->character_id} queue_state_id={$state->id} heartbeat_at={$state->heartbeat_at}");
        }

        $summary = $this->battleRewardResumeService->resumeInterrupted($apply, $staleStates->pluck('id')->all());

        if ($apply) {
            $this->line(
                'Applied reward processing resume: resumed '
                . $summary['resumed_processing_request_count']
                . ', legacy failed '
                . $summary['legacy_failed_processing_request_count']
                . ', restarted processors '
                . $summary['restarted_processor_count']
                . '.',
            );

            return;
        }

        $this->line(
            'Dry run reward processing resume: would resume '
            . $summary['would_resume_processing_request_count']
            . ', would legacy fail '
            . $summary['would_legacy_fail_processing_request_count']
            . '.',
        );
    }
}
