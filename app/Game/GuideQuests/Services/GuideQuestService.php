<?php

namespace App\Game\GuideQuests\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterGuideQuestProgress;
use App\Flare\Models\GuideQuest;
use App\Game\GuideQuests\Services\Requirements\CraftingGuideQuestRequirementService;
use App\Game\GuideQuests\Values\GuideQuestWalkthroughStatus;

class GuideQuestService
{
    /**
     * @param GuideQuestProgressionService $guideQuestProgressionService
     * @param GuideQuestReadinessService $guideQuestReadinessService
     * @param GuideQuestAttentionService $guideQuestAttentionService
     * @param GuideQuestRewardService $guideQuestRewardService
     * @param CraftingGuideQuestRequirementService $craftingGuideQuestRequirementService
     */
    public function __construct(
        private readonly GuideQuestProgressionService $guideQuestProgressionService,
        private readonly GuideQuestReadinessService $guideQuestReadinessService,
        private readonly GuideQuestAttentionService $guideQuestAttentionService,
        private readonly GuideQuestRewardService $guideQuestRewardService,
        private readonly CraftingGuideQuestRequirementService $craftingGuideQuestRequirementService,
    ) {}

    /**
     * Return the Character's currently applicable Guide Quests.
     *
     * @param Character $character
     * @return array
     */
    public function getCurrentQuestsForCharacter(Character $character): array
    {
        return $this->guideQuestProgressionService->currentQuests($character);
    }

    /**
     * Build current Guide Quests with readiness, progress, and attention.
     *
     * @param Character $character
     * @return array
     */
    public function fetchQuestForCharacter(Character $character): array
    {
        $quests = $this->guideQuestProgressionService->currentQuests($character);
        $canHandIn = [];
        $completedRequirements = [];

        foreach ($quests as $quest) {
            $questCanBeHandedIn = $this->guideQuestReadinessService->canHandIn($character, $quest, true);
            $questCompletedRequirements = $this->guideQuestReadinessService->completedRequirements($character, $quest);

            $this->appendWalkthroughStatus($character, $quest);
            $this->guideQuestAttentionService->recordReadyState($character, $quest, $questCanBeHandedIn);

            $canHandIn[] = ['quest_id' => $quest->id, 'can_hand_in' => $questCanBeHandedIn];
            $completedRequirements[] = [
                'quest_id' => $quest->id,
                'completed_requirements' => $questCompletedRequirements,
                'required_batch_crafted_item_requirements' => $this->craftingGuideQuestRequirementService
                    ->batchCraftedItemRequirements($character, $quest->required_batch_crafted_items ?? []),
            ];
        }

        return [
            'quests' => $quests,
            'completed_requirements' => $completedRequirements,
            'can_hand_in' => $canHandIn,
            'has_activity_attention' => $this->guideQuestAttentionService->hasUnacknowledgedAttention($character, $quests),
        ];
    }

    /**
     * Acknowledge attention for current Guide Quests.
     *
     * @param Character $character
     * @return void
     */
    public function acknowledgeAttention(Character $character): void
    {
        $quests = $this->guideQuestProgressionService->currentQuests($character);

        $this->guideQuestAttentionService->acknowledge($character, $quests);
    }

    /**
     * Complete and queue rewards for the Guide Quest.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return bool
     */
    public function handInQuest(Character $character, GuideQuest $quest): bool
    {
        return $this->guideQuestRewardService->handIn($character, $quest);
    }

    /**
     * Apply queued Guide Quest rewards.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return void
     */
    public function processQueuedRewards(Character $character, GuideQuest $quest): void
    {
        $this->guideQuestRewardService->processQueuedRewards($character, $quest);
    }

    /**
     * Determine whether the Guide Quest can be handed in.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @param bool $ignoreAutomation
     * @return bool
     */
    public function canHandInQuest(Character $character, GuideQuest $quest, bool $ignoreAutomation = false): bool
    {
        return $this->guideQuestReadinessService->canHandIn($character, $quest, $ignoreAutomation);
    }

    /**
     * Append lightweight Character walkthrough status.
     *
     * @param Character $character
     * @param GuideQuest $guideQuest
     * @return void
     */
    private function appendWalkthroughStatus(Character $character, GuideQuest $guideQuest): void
    {
        $progress = CharacterGuideQuestProgress::where('character_id', $character->id)
            ->where('guide_quest_id', $guideQuest->id)
            ->first();

        if (! is_null($progress?->walkthrough_completed_at)) {
            $guideQuest->setAttribute('walkthrough_status', GuideQuestWalkthroughStatus::COMPLETED->value);

            return;
        }

        if (! is_null($progress?->started_at)) {
            $guideQuest->setAttribute('walkthrough_status', GuideQuestWalkthroughStatus::IN_PROGRESS->value);

            return;
        }

        $guideQuest->setAttribute('walkthrough_status', GuideQuestWalkthroughStatus::NOT_STARTED->value);
    }
}
