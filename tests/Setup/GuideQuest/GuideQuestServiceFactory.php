<?php

namespace Tests\Setup\GuideQuest;

use App\Game\BattleRewardProcessing\Services\BattleRewardProcessingQueueManager;
use App\Game\Events\Services\GlobalEventGoalEligibilityService;
use App\Game\GuideQuests\Services\GuideQuestAttentionService;
use App\Game\GuideQuests\Services\GuideQuestProgressionService;
use App\Game\GuideQuests\Services\GuideQuestReadinessService;
use App\Game\GuideQuests\Services\GuideQuestRequirementsService;
use App\Game\GuideQuests\Services\GuideQuestRewardService;
use App\Game\GuideQuests\Services\GuideQuestService;
use App\Game\GuideQuests\Services\Requirements\CharacterGuideQuestRequirementService;
use App\Game\GuideQuests\Services\Requirements\CraftingGuideQuestRequirementService;
use App\Game\GuideQuests\Services\Requirements\ExplorationGuideQuestRequirementService;
use App\Game\GuideQuests\Services\Requirements\GlobalEventGuideQuestRequirementService;
use App\Game\GuideQuests\Services\Requirements\KingdomGuideQuestRequirementService;
use App\Game\GuideQuests\Services\Requirements\SkillGuideQuestRequirementService;
use App\Game\GuideQuests\Services\Requirements\WorldGuideQuestRequirementService;

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
        $craftingRequirements = new CraftingGuideQuestRequirementService;
        $requirementsService = $this->buildRequirements($craftingRequirements);
        $readinessService = new GuideQuestReadinessService($requirementsService);
        $rewardService = new GuideQuestRewardService(
            $readinessService,
            $craftingRequirements,
            $queueManager ?? new BattleRewardProcessingQueueManager,
        );

        return new GuideQuestService(
            new GuideQuestProgressionService,
            $readinessService,
            new GuideQuestAttentionService,
            $rewardService,
            $craftingRequirements,
        );
    }

    /**
     * Build the stateless Guide Quest requirements coordinator.
     *
     * @param CraftingGuideQuestRequirementService|null $craftingRequirements
     * @return GuideQuestRequirementsService
     */
    public function buildRequirements(?CraftingGuideQuestRequirementService $craftingRequirements = null): GuideQuestRequirementsService
    {
        return new GuideQuestRequirementsService(
            new CharacterGuideQuestRequirementService,
            new SkillGuideQuestRequirementService,
            new ExplorationGuideQuestRequirementService,
            new WorldGuideQuestRequirementService,
            new KingdomGuideQuestRequirementService,
            $craftingRequirements ?? new CraftingGuideQuestRequirementService,
            new GlobalEventGuideQuestRequirementService(new GlobalEventGoalEligibilityService),
        );
    }
}
