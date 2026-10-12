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
        return $this->guideQuestRequirementsService->completedRequirements($character, $quest);
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
