<?php

namespace App\Game\GuideQuests\Services;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
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
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Support\Facades\Log;

class GuideQuestService
{
    use HandleCharacterLevelUp;

    private GuideQuestRequirementsService $guideQuestRequirementsService;

    private array $completedAttributes = [];

    /**
     * @param GuideQuestRequirementsService $guideQuestRequirementsService
     * @param BattleRewardProcessingQueueManager $battleRewardProcessingQueueManager
     */
    public function __construct(
        GuideQuestRequirementsService $guideQuestRequirementsService,
        private readonly BattleRewardProcessingQueueManager $battleRewardProcessingQueueManager,
    ) {
        $this->guideQuestRequirementsService = $guideQuestRequirementsService;
    }

    /**
     * Return the Character's currently applicable Guide Quests.
     *
     * @param Character $character
     * @return GuideQuest[]
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

            $canHandIn[] = [
                'quest_id' => $quest->id,
                'can_hand_in' => $canHandInQuest,
            ];

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
        ];
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

        if (! $enqueueResult->successful()) {
            $failure = $enqueueResult->failure();

            Log::channel('reward_processing')->error('Guide Quest reward enqueue failed.', [
                'character_id' => $character->id,
                'guide_quest_id' => $quest->id,
                'exception_class' => is_null($failure) ? null : $failure::class,
                'exception_message' => $failure?->getMessage(),
            ]);

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
        $gold = $character->gold + $quest->gold_reward;
        $goldDust = $character->gold_dust + $quest->gold_dust_reward;
        $shards = $character->shards + $quest->shards_reward;

        if ($gold >= CurrencyLimit::MAX_GOLD) {
            $gold = CurrencyLimit::MAX_GOLD;
        }

        if ($goldDust >= CurrencyLimit::MAX_GOLD_DUST) {
            $goldDust = CurrencyLimit::MAX_GOLD_DUST;
        }

        if ($shards >= CurrencyLimit::MAX_SHARDS) {
            $shards = CurrencyLimit::MAX_SHARDS;
        }

        $character = $this->giveXP($character, $quest);

        $character->update([
            'gold' => $gold,
            'gold_dust' => $goldDust,
            'shards' => $shards,
        ]);

        $character = $character->refresh();

        if ($quest->gold_reward > 0) {
            event(new ServerMessageEvent($character->user, 'Rewarded with: '.number_format($quest->gold_reward).' Gold. You now have: '.number_format($character->gold)));
        }

        if ($quest->gold_dust_reward > 0) {
            event(new ServerMessageEvent($character->user, 'Rewarded with: '.number_format($quest->gold_dust_reward).' Gold Dust. You now have: '.number_format($character->gold_dust)));
        }

        if ($quest->shards_reward > 0) {
            event(new ServerMessageEvent($character->user, 'Rewarded with: '.number_format($quest->shards_reward).' Shards. You now have: '.number_format($character->shards)));
        }

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

        $alreadyCompleted = $character->questsCompleted()->where('guide_quest_id', $quest->id)->first();
        $stats = ['str', 'dex', 'dur', 'int', 'chr', 'agi', 'focus'];

        if (! is_null($alreadyCompleted)) {
            return false;
        }

        if ($character->currentAutomations()->where('type', AutomationType::EXPLORING->value)->get()->isNotEmpty() && ! $ignoreAutomation) {
            return false;
        }

        if (! empty($quest->required_batch_crafted_items) && $this->hasActiveBatchCrafting($character)) {
            return false;
        }

        $this->completedAttributes = $this->guideQuestRequirementsService->requiredLevelCheck($character, $quest)
            ->requiredReincarnatedAmount($character, $quest)
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

        if (! empty($this->completedAttributes)) {
            $requiredAttributes = $this->requiredAttributeNames($quest);

            $difference = array_diff($requiredAttributes, $this->completedAttributes);

            $this->guideQuestRequirementsService->resetFinishedRequirements();

            if (empty($difference)) {
                return true;
            }
        }

        return false;
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
            if (($requiredBatchCraftedItem['source'] ?? 'inventory') === 'alchemy_bag') {
                if (! $this->consumeRequiredAlchemyBagItems($character, $requiredBatchCraftedItem)) {
                    return false;
                }

                continue;
            }

            $slotIds = $this->guideQuestRequirementsService->matchingBatchCraftedItemSlotIds($character, $requiredBatchCraftedItem);

            if (count($slotIds) < $requiredBatchCraftedItem['amount']) {
                return false;
            }

            $character->inventory->slots()
                ->whereIn('id', $slotIds)
                ->delete();
        }

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

            if ($alchemyBagSlot->amount <= $remainingAmountToConsume) {
                $remainingAmountToConsume -= $alchemyBagSlot->amount;
                $alchemyBagSlot->delete();

                continue;
            }

            $alchemyBagSlot->update([
                'amount' => $alchemyBagSlot->amount - $remainingAmountToConsume,
            ]);

            $remainingAmountToConsume = 0;
        }

        return $remainingAmountToConsume === 0;
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
     * Resolve the Character's next regular and event Guide Quests.
     *
     * @param Character $character
     * @return array
     */
    private function fetchNextGuideQuest(Character $character): array
    {

        $winterEvent = Event::where('type', EventType::WINTER_EVENT)->first();
        $delusionalEvent = Event::where('type', EventType::DELUSIONAL_MEMORIES_EVENT)->first();
        $unlocksAtLevelQuest = GuideQuest::where('unlock_at_level', '<=', $character->level)->whereNull('only_during_event')->whereNull('parent_id')->orderBy('unlock_at_level', 'asc')->first();
        $nextGuideQuest = null;

        if (! is_null($winterEvent)) {
            $unlocksAtLevelQuest = GuideQuest::where('unlock_at_level', '>=', $character->level)->where('only_during_event', EventType::WINTER_EVENT)->whereNull('parent_id')->orderBy('unlock_at_level', 'asc')->first();
        }

        if (! is_null($delusionalEvent)) {
            $unlocksAtLevelQuest = GuideQuest::where('unlock_at_level', '>=', $character->level)->where('only_during_event', EventType::DELUSIONAL_MEMORIES_EVENT)->whereNull('parent_id')->orderBy('unlock_at_level', 'asc')->first();
        }

        if (! is_null($unlocksAtLevelQuest)) {
            $nextGuideQuest = $this->fetchNextEventQuest($character, $unlocksAtLevelQuest);
        }

        if (! is_null($winterEvent) && is_null($nextGuideQuest)) {
            $eventGuideQuest = GuideQuest::where('only_during_event', EventType::WINTER_EVENT)->whereNull('parent_id')->first();

            if (! is_null($eventGuideQuest)) {
                $nextGuideQuest = $this->fetchNextEventQuest($character, $eventGuideQuest);
            }
        }

        if (! is_null($delusionalEvent) && is_null($nextGuideQuest)) {
            $delusionalEventQuest = GuideQuest::where('only_during_event', EventType::DELUSIONAL_MEMORIES_EVENT)->whereNull('parent_id')->first();

            if (! is_null($delusionalEventQuest)) {
                $nextGuideQuest = $this->fetchNextEventQuest($character, $delusionalEventQuest);
            }
        }

        $regularGuideQuest = $this->fetchNextRegularGuideQuest($character);
        $newFeatureGuideQuest = $nextGuideQuest;

        $guideQuests = [];

        if (! is_null($regularGuideQuest)) {
            $guideQuests[] = $regularGuideQuest;
        }

        if (! is_null($newFeatureGuideQuest)) {
            $guideQuests[] = $newFeatureGuideQuest;
        }

        return $guideQuests;
    }

    /**
     * Resolve the Character's next incomplete regular (non-event) Guide Quest.
     *
     * @param Character $character
     * @return ?GuideQuest
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
     * Depth-first search for the first incomplete regular guide quest.
     *
     * Only descends into a quest's children once the quest itself is
     * completed, so a child is never returned before its parent.
     *
     * @param GuideQuest $quest
     * @param array $completedGuideQuestIds
     * @return ?GuideQuest
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
     * @return ?GuideQuest
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
     * List the Guide Quest's populated `required_*` attribute names.
     *
     * @param GuideQuest $quest
     * @return array
     */
    private function requiredAttributeNames(GuideQuest $quest): array
    {

        $requiredAttributes = [];

        $attributes = $quest->getAttributes();

        foreach ($attributes as $key => $value) {
            if ($key === 'required_skill') {
                continue;
            }

            if ($key === 'required_passive_skill') {
                continue;
            }

            if ($key === 'required_faction_id') {
                continue;
            }

            if ($key === 'required_secondary_skill') {
                continue;
            }

            if ($key === 'required_skill_type') {
                continue;
            }

            if ($key === 'required_kingdom_building_id') {
                continue;
            }

            if ($key === 'required_batch_crafting_type') {
                continue;
            }

            if (str_contains($key, 'required') !== false) {
                if (! is_null($attributes[$key])) {
                    $requiredAttributes[] = $key;
                }
            }
        }

        return $requiredAttributes;
    }
}
