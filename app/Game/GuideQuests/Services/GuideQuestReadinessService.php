<?php

namespace App\Game\GuideQuests\Services;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\GuideQuest;
use App\Game\Automation\Values\AutomationType;

class GuideQuestReadinessService
{
    /**
     * @param GuideQuestRequirementsService $guideQuestRequirementsService
     */
    public function __construct(
        private readonly GuideQuestRequirementsService $guideQuestRequirementsService,
    ) {}

    /**
     * Determine whether the Character satisfies every Guide Quest requirement.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @param bool $ignoreAutomation
     * @return bool
     */
    public function canHandIn(Character $character, GuideQuest $quest, bool $ignoreAutomation = false): bool
    {
        if ($character->questsCompleted()->where('guide_quest_id', $quest->id)->exists()) {
            return false;
        }

        if (! $ignoreAutomation && $character->currentAutomations()->where('type', AutomationType::EXPLORING->value)->exists()) {
            return false;
        }

        if (! empty($quest->required_batch_crafted_items) && $this->hasActiveBatchCrafting($character)) {
            return false;
        }

        $completedRequirements = $this->completedRequirements($character, $quest);

        if (empty($completedRequirements)) {
            return false;
        }

        return empty(array_diff($this->requiredAttributeNames($quest), $completedRequirements));
    }

    /**
     * Return the Guide Quest requirement keys completed by the Character.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return array
     */
    public function completedRequirements(Character $character, GuideQuest $quest): array
    {
        $stats = ['str', 'dex', 'dur', 'int', 'chr', 'agi', 'focus'];
        $completedRequirements = $this->guideQuestRequirementsService->requiredLevelCheck($character, $quest)
            ->requiredReincarnatedAmount($character, $quest)
            ->requiredExplorationHours($character, $quest)
            ->requiredTrainingSkill($character, $quest)
            ->requiredQuest($character, $quest)
            ->requiredSkillCheck($character, $quest)
            ->requiredSkillCheck($character, $quest, false)
            ->requiredSkillTypeCheck($character, $quest)
            ->requiredFactionLevel($character, $quest)
            ->requiredGameMapAccess($character, $quest)
            ->requiredQuestItem($character, $quest)
            ->requiredQuestItem($character, $quest, false)
            ->requiredKingdomCount($character, $quest)
            ->requiredKingdomBuildingLevel($character, $quest)
            ->requiredKingdomUnitCount($character, $quest)
            ->requiredKingdomPassiveLevel($character, $quest)
            ->requiredCurrency($character, $quest, 'gold')
            ->requiredCurrency($character, $quest, 'gold_dust')
            ->requiredCurrency($character, $quest, 'shards')
            ->requiredCurrency($character, $quest, 'copper_coins')
            ->requiredTotalStats($character, $quest, $stats)
            ->requiredStats($character, $quest, $stats)
            ->requiredClassRanksEquipped($character, $quest)
            ->requiredClassRankLevel($character, $quest)
            ->requiredKingdomGoldBarsAmount($character, $quest)
            ->requiredKingdomSpecificBuildingLevel($character, $quest)
            ->requiredGlobalEventKillAmount($character, $quest)
            ->requiredGlobalEventCraftAmount($character, $quest)
            ->requiredGlobalEventEnchantAmount($character, $quest)
            ->requirePlayerToBeOnASpecificMap($character, $quest)
            ->requiredSpecialtyType($character, $quest)
            ->requiredHolyStacks($character, $quest)
            ->requiredFameLevel($character, $quest)
            ->requiredDelveSurvivalTime($character, $quest)
            ->requiredDelvePackSize($character, $quest)
            ->requiredBatchCraftingExperienceHours($character, $quest)
            ->requiredBatchCraftedItems($character, $quest)
            ->getFinishedRequirements();

        $this->guideQuestRequirementsService->resetFinishedRequirements();

        return $completedRequirements;
    }

    /**
     * Return factual progress for the Guide Quest's batch-crafted Item requirements.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return array
     */
    public function batchCraftedItemRequirements(Character $character, GuideQuest $quest): array
    {
        return $this->guideQuestRequirementsService->batchCraftedItemRequirements(
            $character,
            $quest->required_batch_crafted_items ?? [],
        );
    }

    /**
     * List populated Guide Quest attributes that represent completable requirements.
     *
     * @param GuideQuest $quest
     * @return array
     */
    private function requiredAttributeNames(GuideQuest $quest): array
    {
        $companionAttributes = [
            'required_skill',
            'required_training_skill',
            'required_passive_skill',
            'required_faction_id',
            'required_secondary_skill',
            'required_skill_type',
            'required_kingdom_building_id',
            'required_batch_crafting_type',
        ];

        return collect($quest->getAttributes())
            ->filter(fn (mixed $value, string $key): bool => str_contains($key, 'required')
                && ! in_array($key, $companionAttributes, true)
                && ! is_null($value))
            ->keys()
            ->all();
    }

    /**
     * Determine whether the Character has active Batch Crafting work.
     *
     * @param Character $character
     * @return bool
     */
    private function hasActiveBatchCrafting(Character $character): bool
    {
        return BatchCrafting::where('character_id', $character->id)
            ->whereNull('completed_at')
            ->exists();
    }
}
