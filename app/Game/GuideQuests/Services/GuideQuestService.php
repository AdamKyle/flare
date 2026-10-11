<?php

namespace App\Game\GuideQuests\Services;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterGuideQuestProgress;
use App\Flare\Models\Event;
use App\Flare\Models\GuideQuest;
use App\Flare\Models\QuestsCompleted;
use App\Game\Automation\Values\AutomationType;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestPriority;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Services\BattleRewardProcessingQueueManager;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Traits\HandleCharacterLevelUp;
use App\Game\Events\Values\EventType;
use App\Game\GuideQuests\Events\ShowGuideQuestCompletedToast;
use App\Game\GuideQuests\Values\GuideQuestRequiredItemSource;
use App\Game\GuideQuests\Values\GuideQuestWalkthroughStatus;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Support\Facades\Log;

class GuideQuestService
{
    use HandleCharacterLevelUp;

    private array $completedAttributes = [];

    /**
     * @param GuideQuestRequirementsService $guideQuestRequirementsService
     * @param BattleRewardProcessingQueueManager $battleRewardProcessingQueueManager
     */
    public function __construct(
        private readonly GuideQuestRequirementsService $guideQuestRequirementsService,
        private readonly BattleRewardProcessingQueueManager $battleRewardProcessingQueueManager,
    ) {}

    /**
     * Return the Character's currently applicable Guide Quests.
     *
     * @param Character $character
     * @return array
     */
    public function getCurrentQuestsForCharacter(Character $character): array
    {
        return $this->fetchNextGuideQuest($character);
    }

    /**
     * Build the Character's current Guide Quest list with hand-in eligibility and completed requirements.
     *
     * @param Character $character
     * @return array
     */
    public function fetchQuestForCharacter(Character $character): array
    {
        $quests = $this->fetchNextGuideQuest($character);

        $canHandIn = [];
        $completedAttributes = [];

        foreach ($quests as $quest) {
            $canHandInQuest = $this->canHandInQuest($character, $quest, true);
            $this->appendWalkthroughStatus($character, $quest);

            $canHandIn[] = [
                'quest_id' => $quest->id,
                'can_hand_in' => $canHandInQuest,
            ];

            $this->recordNewHandInAttention($character, $quest, $canHandInQuest);

            $completedAttributes[] = [
                'quest_id' => $quest->id,
                'completed_requirements' => $this->completedAttributes,
                'required_batch_crafted_item_requirements' => $this->guideQuestRequirementsService->batchCraftedItemRequirements(
                    $character,
                    $quest->required_batch_crafted_items ?? [],
                ),
            ];
        }

        return [
            'quests' => $quests,
            'completed_requirements' => $completedAttributes,
            'can_hand_in' => $canHandIn,
            'has_activity_attention' => $this->hasUnacknowledgedAttention($character, $quests),
        ];
    }

    /**
     * Acknowledge attention for the Character's currently applicable Guide Quests.
     *
     * @param Character $character
     * @return void
     */
    public function acknowledgeAttention(Character $character): void
    {
        $questIds = collect($this->fetchNextGuideQuest($character))->pluck('id');

        CharacterGuideQuestProgress::where('character_id', $character->id)
            ->whereIn('guide_quest_id', $questIds)
            ->whereNotNull('hand_in_ready_at')
            ->whereNull('attention_acknowledged_at')
            ->update(['attention_acknowledged_at' => now()]);
    }

    /**
     * Complete and hand in the Guide Quest for the Character, queuing its reward.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return bool
     */
    public function handInQuest(Character $character, GuideQuest $quest): bool
    {
        $character = Character::find($character->id);
        $quest = GuideQuest::find($quest->id);

        if (is_null($character) || is_null($quest)) {
            return false;
        }

        if (! $this->canHandInQuest($character, $quest)) {
            return false;
        }

        $completion = QuestsCompleted::firstOrCreate([
            'character_id' => $character->id,
            'guide_quest_id' => $quest->id,
        ]);

        if (! $completion->wasRecentlyCreated) {
            return false;
        }

        if (! $this->consumeRequiredBatchCraftedItems($character, $quest)) {
            $completion->delete();

            return false;
        }

        if (! $this->enqueueGuideQuestReward($character, $quest)) {
            $completion->delete();

            return false;
        }

        event(new ShowGuideQuestCompletedToast($character->user, false));

        return true;
    }

    /**
     * Apply the Guide Quest's queued currency and XP rewards to the Character.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return void
     */
    public function processQueuedRewards(Character $character, GuideQuest $quest): void
    {
        $gold = min($character->gold + $quest->gold_reward, CurrencyLimit::MAX_GOLD);
        $goldDust = min($character->gold_dust + $quest->gold_dust_reward, CurrencyLimit::MAX_GOLD_DUST);
        $shards = min($character->shards + $quest->shards_reward, CurrencyLimit::MAX_SHARDS);

        $character = $this->giveXP($character, $quest);

        $character->update([
            'gold' => $gold,
            'gold_dust' => $goldDust,
            'shards' => $shards,
        ]);

        $character = $character->refresh();

        $this->announceCurrencyReward($character, $quest->gold_reward, 'Gold', $character->gold);
        $this->announceCurrencyReward($character, $quest->gold_dust_reward, 'Gold Dust', $character->gold_dust);
        $this->announceCurrencyReward($character, $quest->shards_reward, 'Shards', $character->shards);

        event(new UpdateTopBarEvent($character));
    }

    /**
     * Determine whether the Character currently satisfies every requirement of the Guide Quest.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @param bool $ignoreAutomation
     * @return bool
     */
    public function canHandInQuest(Character $character, GuideQuest $quest, bool $ignoreAutomation = false): bool
    {
        $this->completedAttributes = [];

        if ($character->questsCompleted()->where('guide_quest_id', $quest->id)->exists()) {
            return false;
        }

        if (! $ignoreAutomation && $character->currentAutomations()->where('type', AutomationType::EXPLORING->value)->exists()) {
            return false;
        }

        if (! empty($quest->required_batch_crafted_items) && $this->hasActiveBatchCrafting($character)) {
            return false;
        }

        $this->completedAttributes = $this->resolveCompletedRequirements($character, $quest);

        if (empty($this->completedAttributes)) {
            return false;
        }

        $difference = array_diff($this->requiredAttributeNames($quest), $this->completedAttributes);

        $this->guideQuestRequirementsService->resetFinishedRequirements();

        return empty($difference);
    }

    /**
     * Append lightweight Character walkthrough status without exposing step definitions.
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

    /**
     * Persist the first transition into a hand-in-ready state.
     *
     * @param Character $character
     * @param GuideQuest $guideQuest
     * @param bool $canHandIn
     * @return void
     */
    private function recordNewHandInAttention(Character $character, GuideQuest $guideQuest, bool $canHandIn): void
    {
        if (! $canHandIn) {
            return;
        }

        CharacterGuideQuestProgress::firstOrCreate(
            [
                'character_id' => $character->id,
                'guide_quest_id' => $guideQuest->id,
            ],
            ['hand_in_ready_at' => now()],
        );

        CharacterGuideQuestProgress::where('character_id', $character->id)
            ->where('guide_quest_id', $guideQuest->id)
            ->whereNull('hand_in_ready_at')
            ->update(['hand_in_ready_at' => now()]);
    }

    /**
     * Determine whether current Guide Quests have unacknowledged ready attention.
     *
     * @param Character $character
     * @param array $quests
     * @return bool
     */
    private function hasUnacknowledgedAttention(Character $character, array $quests): bool
    {
        return CharacterGuideQuestProgress::where('character_id', $character->id)
            ->whereIn('guide_quest_id', collect($quests)->pluck('id'))
            ->whereNotNull('hand_in_ready_at')
            ->whereNull('attention_acknowledged_at')
            ->exists();
    }

    /**
     * Queue the Guide Quest reward, logging the failure when the reward cannot be queued.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return bool
     */
    private function enqueueGuideQuestReward(Character $character, GuideQuest $quest): bool
    {
        $enqueueResult = $this->battleRewardProcessingQueueManager->enqueue(
            $character,
            BattleRewardRequestPriority::FIRST,
            BattleRewardRequestSourceType::GUIDE_QUEST,
            implode(':', [
                BattleRewardRequestSourceType::GUIDE_QUEST->value,
                $character->id,
                $quest->id,
            ]),
            [
                'character_id' => $character->id,
                'guide_quest_id' => $quest->id,
            ],
        );

        if ($enqueueResult->successful()) {
            return true;
        }

        $failure = $enqueueResult->failure();

        Log::channel('reward_processing')->error('Guide Quest reward enqueue failed.', [
            'character_id' => $character->id,
            'guide_quest_id' => $quest->id,
            'exception_class' => is_null($failure) ? null : $failure::class,
            'exception_message' => $failure?->getMessage(),
        ]);

        return false;
    }

    /**
     * Tell the Character about a positive currency reward and their new total.
     *
     * @param Character $character
     * @param int|null $reward
     * @param string $currencyName
     * @param int $total
     * @return void
     */
    private function announceCurrencyReward(Character $character, ?int $reward, string $currencyName, int $total): void
    {
        if ($reward <= 0) {
            return;
        }

        event(new ServerMessageEvent($character->user, 'Rewarded with: '.number_format($reward).' '.$currencyName.'. You now have: '.number_format($total)));
    }

    /**
     * Resolve which of the Guide Quest's requirements the Character has already met.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return array
     */
    private function resolveCompletedRequirements(Character $character, GuideQuest $quest): array
    {
        $stats = ['str', 'dex', 'dur', 'int', 'chr', 'agi', 'focus'];

        return $this->guideQuestRequirementsService->requiredLevelCheck($character, $quest)
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
    }

    /**
     * Award the Guide Quest's XP reward to the Character and handle any resulting level up.
     *
     * @param Character $character
     * @param GuideQuest $guideQuest
     * @return Character
     */
    private function giveXP(Character $character, GuideQuest $guideQuest): Character
    {
        if ($guideQuest->xp_reward <= 0) {
            return $character;
        }

        $character->update([
            'xp' => $character->xp + $guideQuest->xp_reward,
        ]);

        $character = $character->refresh();

        $this->handlePossibleLevelUp($character);

        event(new ServerMessageEvent($character->user, 'Rewarded with: '.number_format($guideQuest->xp_reward).' XP.'));

        return $character;
    }

    /**
     * Consume the Guide Quest's required batch-crafted or alchemy bag items from the Character.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return bool
     */
    private function consumeRequiredBatchCraftedItems(Character $character, GuideQuest $quest): bool
    {
        if (empty($quest->required_batch_crafted_items)) {
            return true;
        }

        if ($this->hasActiveBatchCrafting($character)) {
            return false;
        }

        if (! $this->guideQuestRequirementsService->hasRequiredBatchCraftedItems($character, $quest->required_batch_crafted_items)) {
            return false;
        }

        foreach ($quest->required_batch_crafted_items as $requiredBatchCraftedItem) {
            if (! $this->consumeRequiredItem($character, $requiredBatchCraftedItem)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Consume one required item entry from the inventory or Alchemy Bag source it names.
     *
     * @param Character $character
     * @param array $requiredBatchCraftedItem
     * @return bool
     */
    private function consumeRequiredItem(Character $character, array $requiredBatchCraftedItem): bool
    {
        $source = GuideQuestRequiredItemSource::tryFrom($requiredBatchCraftedItem['source'] ?? '') ?? GuideQuestRequiredItemSource::INVENTORY;

        if ($source === GuideQuestRequiredItemSource::ALCHEMY_BAG) {
            return $this->consumeRequiredAlchemyBagItems($character, $requiredBatchCraftedItem);
        }

        $slotIds = $this->guideQuestRequirementsService->matchingBatchCraftedItemSlotIds($character, $requiredBatchCraftedItem);

        if (count($slotIds) < $requiredBatchCraftedItem['amount']) {
            return false;
        }

        $character->inventory->slots()
            ->whereIn('id', $slotIds)
            ->delete();

        return true;
    }

    /**
     * Consume the required amount of a single Alchemy Bag item from the Character.
     *
     * @param Character $character
     * @param array $requiredBatchCraftedItem
     * @return bool
     */
    private function consumeRequiredAlchemyBagItems(Character $character, array $requiredBatchCraftedItem): bool
    {
        if (! $this->guideQuestRequirementsService->hasRequiredAlchemyBagItemAmount($character, $requiredBatchCraftedItem)) {
            return false;
        }

        $remainingAmountToConsume = $requiredBatchCraftedItem['amount'];
        $alchemyBagSlots = AlchemyBagSlot::where('character_id', $character->id)
            ->where('item_id', $requiredBatchCraftedItem['item_id'])
            ->orderBy('id')
            ->get();

        foreach ($alchemyBagSlots as $alchemyBagSlot) {
            if ($remainingAmountToConsume <= 0) {
                break;
            }

            $remainingAmountToConsume = $this->consumeFromAlchemyBagSlot($alchemyBagSlot, $remainingAmountToConsume);
        }

        return $remainingAmountToConsume === 0;
    }

    /**
     * Take up to the remaining amount from one Alchemy Bag slot and return what is still left to consume.
     *
     * @param AlchemyBagSlot $alchemyBagSlot
     * @param int $remainingAmountToConsume
     * @return int
     */
    private function consumeFromAlchemyBagSlot(AlchemyBagSlot $alchemyBagSlot, int $remainingAmountToConsume): int
    {
        if ($alchemyBagSlot->amount <= $remainingAmountToConsume) {
            $alchemyBagSlot->delete();

            return $remainingAmountToConsume - $alchemyBagSlot->amount;
        }

        $alchemyBagSlot->update([
            'amount' => $alchemyBagSlot->amount - $remainingAmountToConsume,
        ]);

        return 0;
    }

    /**
     * Determine whether the Character has an active, uncompleted Batch Crafting run.
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

    /**
     * Resolve the Character's next regular and event or new-feature Guide Quests.
     *
     * @param Character $character
     * @return array
     */
    private function fetchNextGuideQuest(Character $character): array
    {
        return array_values(array_filter([
            $this->fetchNextRegularGuideQuest($character),
            $this->fetchNextFeatureGuideQuest($character),
        ]));
    }

    /**
     * Resolve the Character's next level-unlocked or running-event Guide Quest.
     *
     * @param Character $character
     * @return GuideQuest|null
     */
    private function fetchNextFeatureGuideQuest(Character $character): ?GuideQuest
    {
        $activeEventTypes = $this->activeGuideQuestEventTypes();
        $unlocksAtLevelQuest = $this->unlocksAtLevelGuideQuest($character, $activeEventTypes);

        $nextGuideQuest = is_null($unlocksAtLevelQuest) ? null : $this->fetchNextEventQuest($character, $unlocksAtLevelQuest);

        if (! is_null($nextGuideQuest)) {
            return $nextGuideQuest;
        }

        foreach ($activeEventTypes as $eventType) {
            $eventGuideQuest = $this->fetchNextEventRootQuest($character, $eventType);

            if (! is_null($eventGuideQuest)) {
                return $eventGuideQuest;
            }
        }

        return null;
    }

    /**
     * Return the running events that own Guide Quest chains, in precedence order.
     *
     * @return array
     */
    private function activeGuideQuestEventTypes(): array
    {
        return array_values(array_filter(
            [EventType::WINTER_EVENT, EventType::DELUSIONAL_MEMORIES_EVENT],
            fn (int $eventType): bool => Event::where('type', $eventType)->exists(),
        ));
    }

    /**
     * Resolve the level-unlocked Guide Quest, preferring the latest running event's chain when one is running.
     *
     * @param Character $character
     * @param array $activeEventTypes
     * @return GuideQuest|null
     */
    private function unlocksAtLevelGuideQuest(Character $character, array $activeEventTypes): ?GuideQuest
    {
        if (empty($activeEventTypes)) {
            return GuideQuest::where('unlock_at_level', '<=', $character->level)
                ->whereNull('only_during_event')
                ->whereNull('parent_id')
                ->orderBy('unlock_at_level', 'asc')
                ->first();
        }

        return GuideQuest::where('unlock_at_level', '>=', $character->level)
            ->where('only_during_event', $activeEventTypes[array_key_last($activeEventTypes)])
            ->whereNull('parent_id')
            ->orderBy('unlock_at_level', 'asc')
            ->first();
    }

    /**
     * Resolve the Character's next incomplete quest from the root of an event's Guide Quest chain.
     *
     * @param Character $character
     * @param int $eventType
     * @return GuideQuest|null
     */
    private function fetchNextEventRootQuest(Character $character, int $eventType): ?GuideQuest
    {
        $eventGuideQuest = GuideQuest::where('only_during_event', $eventType)->whereNull('parent_id')->first();

        if (is_null($eventGuideQuest)) {
            return null;
        }

        return $this->fetchNextEventQuest($character, $eventGuideQuest);
    }

    /**
     * Resolve the Character's next incomplete regular (non-event) Guide Quest.
     *
     * @param Character $character
     * @return GuideQuest|null
     */
    private function fetchNextRegularGuideQuest(Character $character): ?GuideQuest
    {
        $completedGuideQuestIds = $character->questsCompleted()
            ->whereNotNull('guide_quest_id')
            ->pluck('guide_quest_id')
            ->all();

        $roots = GuideQuest::whereNull('only_during_event')
            ->whereNull('unlock_at_level')
            ->whereNull('parent_id')
            ->orderBy('id')
            ->get();

        foreach ($roots as $root) {
            $nextIncompleteQuest = $this->findNextIncompleteRegularGuideQuest($root, $completedGuideQuestIds);

            if (! is_null($nextIncompleteQuest)) {
                return $nextIncompleteQuest;
            }
        }

        return null;
    }

    /**
     * Find the first incomplete regular Guide Quest depth-first, never returning a child before its parent is complete.
     *
     * @param GuideQuest $quest
     * @param array $completedGuideQuestIds
     * @return GuideQuest|null
     */
    private function findNextIncompleteRegularGuideQuest(GuideQuest $quest, array $completedGuideQuestIds): ?GuideQuest
    {
        if (! in_array($quest->id, $completedGuideQuestIds, true)) {
            return $quest;
        }

        $children = GuideQuest::whereNull('only_during_event')
            ->whereNull('unlock_at_level')
            ->where('parent_id', $quest->id)
            ->orderBy('id')
            ->get();

        foreach ($children as $child) {
            $nextIncompleteQuest = $this->findNextIncompleteRegularGuideQuest($child, $completedGuideQuestIds);

            if (! is_null($nextIncompleteQuest)) {
                return $nextIncompleteQuest;
            }
        }

        return null;
    }

    /**
     * Resolve the Character's next incomplete quest in an event Guide Quest chain.
     *
     * @param Character $character
     * @param GuideQuest $initialEventGuideQuest
     * @return GuideQuest|null
     */
    private function fetchNextEventQuest(Character $character, GuideQuest $initialEventGuideQuest): ?GuideQuest
    {
        $completedFirstEventQuest = $character->questsCompleted()
            ->where('guide_quest_id', $initialEventGuideQuest->id)
            ->first();

        if (is_null($completedFirstEventQuest)) {
            return $initialEventGuideQuest;
        }

        $nextGuideQuest = GuideQuest::where('parent_id', $initialEventGuideQuest->id)->orderBy('id')->first();

        if (is_null($nextGuideQuest)) {
            return null;
        }

        return $this->fetchNextEventQuest($character, $nextGuideQuest);
    }

    /**
     * List the Guide Quest's populated `required_*` attribute names that are checked as completable requirements.
     *
     * @param GuideQuest $quest
     * @return array
     */
    private function requiredAttributeNames(GuideQuest $quest): array
    {
        $attributesCheckedThroughCompanions = [
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
                && ! in_array($key, $attributesCheckedThroughCompanions, true)
                && ! is_null($value))
            ->keys()
            ->all();
    }
}
