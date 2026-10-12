<?php

namespace Tests\Setup\GuideQuest;

use App\Game\BattleRewardProcessing\Services\BattleRewardProcessingQueueManager;
use App\Game\GuideQuests\Services\GuideQuestAttentionService;
use App\Game\GuideQuests\Services\GuideQuestProgressionService;
use App\Game\GuideQuests\Services\GuideQuestReadinessService;
use App\Game\GuideQuests\Services\GuideQuestRequirementsService;
use App\Game\GuideQuests\Services\GuideQuestRewardService;
use App\Game\GuideQuests\Services\GuideQuestService;

class GuideQuestServiceFactory
{
    /**
     * Build a real GuideQuestService, optionally with a supplied reward queue manager.
     *
     * @param BattleRewardProcessingQueueManager|null $queueManager
     * @return GuideQuestService
     */
    public function build(?BattleRewardProcessingQueueManager $queueManager = null): GuideQuestService
    {
        $requirementsService = new GuideQuestRequirementsService;
        $readinessService = new GuideQuestReadinessService($requirementsService);
        $rewardService = new GuideQuestRewardService(
            $readinessService,
            $requirementsService,
            $queueManager ?? new BattleRewardProcessingQueueManager,
        );

        return new GuideQuestService(
            new GuideQuestProgressionService,
            $readinessService,
            new GuideQuestAttentionService,
            $rewardService,
        );
    }
}
