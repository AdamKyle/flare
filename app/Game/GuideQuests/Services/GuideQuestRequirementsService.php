<?php

namespace App\Game\GuideQuests\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GuideQuest;
use App\Game\GuideQuests\Services\Requirements\CharacterGuideQuestRequirementService;
use App\Game\GuideQuests\Services\Requirements\CraftingGuideQuestRequirementService;
use App\Game\GuideQuests\Services\Requirements\ExplorationGuideQuestRequirementService;
use App\Game\GuideQuests\Services\Requirements\GlobalEventGuideQuestRequirementService;
use App\Game\GuideQuests\Services\Requirements\KingdomGuideQuestRequirementService;
use App\Game\GuideQuests\Services\Requirements\SkillGuideQuestRequirementService;
use App\Game\GuideQuests\Services\Requirements\WorldGuideQuestRequirementService;

class GuideQuestRequirementsService
{
    /**
     * @param CharacterGuideQuestRequirementService $characterRequirements
     * @param SkillGuideQuestRequirementService $skillRequirements
     * @param ExplorationGuideQuestRequirementService $explorationRequirements
     * @param WorldGuideQuestRequirementService $worldRequirements
     * @param KingdomGuideQuestRequirementService $kingdomRequirements
     * @param CraftingGuideQuestRequirementService $craftingRequirements
     * @param GlobalEventGuideQuestRequirementService $globalEventRequirements
     */
    public function __construct(
        private readonly CharacterGuideQuestRequirementService $characterRequirements,
        private readonly SkillGuideQuestRequirementService $skillRequirements,
        private readonly ExplorationGuideQuestRequirementService $explorationRequirements,
        private readonly WorldGuideQuestRequirementService $worldRequirements,
        private readonly KingdomGuideQuestRequirementService $kingdomRequirements,
        private readonly CraftingGuideQuestRequirementService $craftingRequirements,
        private readonly GlobalEventGuideQuestRequirementService $globalEventRequirements,
    ) {}

    /**
     * Return every Guide Quest requirement completed by the Character.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return array
     */
    public function completedRequirements(Character $character, GuideQuest $quest): array
    {
        $requirements = array_merge(
            $this->characterRequirements->completedRequirements($character, $quest),
            $this->skillRequirements->completedRequirements($character, $quest),
            $this->explorationRequirements->completedRequirements($character, $quest),
            $this->worldRequirements->completedRequirements($character, $quest),
            $this->kingdomRequirements->completedRequirements($character, $quest),
            $this->craftingRequirements->completedRequirements($character, $quest),
            $this->globalEventRequirements->completedRequirements($character, $quest),
        );

        return array_values(array_unique($requirements));
    }
}
