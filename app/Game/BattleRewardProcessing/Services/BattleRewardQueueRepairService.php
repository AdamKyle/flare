<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Admin\Services\MonitoredBugReportService;

class BattleRewardQueueRepairService
{
    public const FAILED_REASON = 'Queue processor heartbeat became stale and was repaired by admin repair. Reward was not retried automatically.';

    public function __construct(
        private readonly BattleRewardResumeService $battleRewardResumeService,
        private readonly MonitoredBugReportService $monitoredBugReportService,
    ) {}

    public function repairStaleQueues(): array
    {
        $summary = $this->battleRewardResumeService->resumeInterrupted();

        if ($summary['legacy_failed_processing_request_count'] > 0) {
            $this->monitoredBugReportService->reportError(
                'battle-reward-queue-repair',
                'Legacy pre-ledger reward queue repaired: ' . $summary['legacy_failed_processing_request_count'] . ' processing request(s) marked failed.',
                ['legacy_failed_processing_request_count' => $summary['legacy_failed_processing_request_count']],
            );
        }

        return $summary;
    }
}
