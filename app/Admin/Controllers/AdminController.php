<?php

namespace App\Admin\Controllers;

use App\Admin\Services\AdminMonitoringService;
use App\Admin\Services\BattleRewardQueueAdminService;
use App\Admin\Services\FeedbackService;
use App\Http\Controllers\Controller;

class AdminController extends Controller
{
    public function __construct(
        private readonly FeedbackService $feedbackService,
        private readonly BattleRewardQueueAdminService $battleRewardQueueAdminService,
        private readonly AdminMonitoringService $adminMonitoringService,
    ) {}

    public function home()
    {

        return view('admin.home', [
            ...$this->feedbackService->gatherFeedbackData(),
            'rewardQueueSummary' => $this->battleRewardQueueAdminService->summary(),
            'rewardQueueLastHour' => $this->battleRewardQueueAdminService->lastHourChart(),
            'exploringCount' => $this->adminMonitoringService->activeExplorationCount(),
            'factionLoyaltyCount' => $this->adminMonitoringService->activeFactionLoyaltyCount(),
            'delveCount' => $this->adminMonitoringService->activeDelveCount(),
        ]);
    }

    public function chatLogs()
    {
        return view('admin.chat.logs');
    }
}
