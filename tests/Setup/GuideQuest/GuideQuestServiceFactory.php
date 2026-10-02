<?php

namespace Tests\Setup\GuideQuest;

use App\Game\BattleRewardProcessing\Services\BattleRewardProcessingQueueManager;
use App\Game\GuideQuests\Services\GuideQuestRequirementsService;
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
        return new GuideQuestService(
            new GuideQuestRequirementsService(),
            $queueManager ?? new BattleRewardProcessingQueueManager(),
        );
    }
}
