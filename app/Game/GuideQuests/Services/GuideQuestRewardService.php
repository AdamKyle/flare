<?php

namespace App\Game\GuideQuests\Services;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\GuideQuest;
use App\Flare\Models\QuestsCompleted;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestPriority;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Services\BattleRewardProcessingQueueManager;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Traits\HandleCharacterLevelUp;
use App\Game\GuideQuests\Events\ShowGuideQuestCompletedToast;
use App\Game\GuideQuests\Values\GuideQuestRequiredItemSource;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Support\Facades\Log;

class GuideQuestRewardService
{
    use HandleCharacterLevelUp;

    /**
     * @param GuideQuestReadinessService $guideQuestReadinessService
     * @param GuideQuestRequirementsService $guideQuestRequirementsService
     * @param BattleRewardProcessingQueueManager $battleRewardProcessingQueueManager
     */
    public function __construct(
        private readonly GuideQuestReadinessService $guideQuestReadinessService,
        private readonly GuideQuestRequirementsService $guideQuestRequirementsService,
        private readonly BattleRewardProcessingQueueManager $battleRewardProcessingQueueManager,
    ) {}

    /**
     * Complete the Guide Quest and queue its reward.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return bool
     */
    public function handIn(Character $character, GuideQuest $quest): bool
    {
        $character = Character::find($character->id);
        $quest = GuideQuest::find($quest->id);

        if (is_null($character) || is_null($quest)) {
            return false;
        }

        if (! $this->guideQuestReadinessService->canHandIn($character, $quest)) {
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
     * Apply queued Guide Quest currency and XP rewards.
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

        $character->update(['gold' => $gold, 'gold_dust' => $goldDust, 'shards' => $shards]);
        $character = $character->refresh();

        $this->announceCurrencyReward($character, $quest->gold_reward, 'Gold', $character->gold);
        $this->announceCurrencyReward($character, $quest->gold_dust_reward, 'Gold Dust', $character->gold_dust);
        $this->announceCurrencyReward($character, $quest->shards_reward, 'Shards', $character->shards);

        event(new UpdateTopBarEvent($character));
    }

    /**
     * Queue the Guide Quest reward and log failures.
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
            implode(':', [BattleRewardRequestSourceType::GUIDE_QUEST->value, $character->id, $quest->id]),
            ['character_id' => $character->id, 'guide_quest_id' => $quest->id],
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
     * Announce a positive currency reward and its new total.
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
     * Award XP and process any resulting level up.
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

        $character->update(['xp' => $character->xp + $guideQuest->xp_reward]);
        $character = $character->refresh();

        $this->handlePossibleLevelUp($character);
        event(new ServerMessageEvent($character->user, 'Rewarded with: '.number_format($guideQuest->xp_reward).' XP.'));

        return $character;
    }

    /**
     * Consume required batch-crafted or Alchemy Bag items.
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

        foreach ($quest->required_batch_crafted_items as $requiredItem) {
            if (! $this->consumeRequiredItem($character, $requiredItem)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Consume one required item from its configured source.
     *
     * @param Character $character
     * @param array $requiredItem
     * @return bool
     */
    private function consumeRequiredItem(Character $character, array $requiredItem): bool
    {
        $source = GuideQuestRequiredItemSource::tryFrom($requiredItem['source'] ?? '')
            ?? GuideQuestRequiredItemSource::INVENTORY;

        if ($source === GuideQuestRequiredItemSource::ALCHEMY_BAG) {
            return $this->consumeRequiredAlchemyBagItems($character, $requiredItem);
        }

        $slotIds = $this->guideQuestRequirementsService->matchingBatchCraftedItemSlotIds($character, $requiredItem);

        if (count($slotIds) < $requiredItem['amount']) {
            return false;
        }

        $character->inventory->slots()->whereIn('id', $slotIds)->delete();

        return true;
    }

    /**
     * Consume one required Alchemy Bag item amount.
     *
     * @param Character $character
     * @param array $requiredItem
     * @return bool
     */
    private function consumeRequiredAlchemyBagItems(Character $character, array $requiredItem): bool
    {
        if (! $this->guideQuestRequirementsService->hasRequiredAlchemyBagItemAmount($character, $requiredItem)) {
            return false;
        }

        $remainingAmount = $requiredItem['amount'];
        $alchemyBagSlots = AlchemyBagSlot::where('character_id', $character->id)
            ->where('item_id', $requiredItem['item_id'])
            ->orderBy('id')
            ->get();

        foreach ($alchemyBagSlots as $alchemyBagSlot) {
            if ($remainingAmount <= 0) {
                break;
            }

            $remainingAmount = $this->consumeFromAlchemyBagSlot($alchemyBagSlot, $remainingAmount);
        }

        return $remainingAmount === 0;
    }

    /**
     * Consume from one Alchemy Bag slot and return the outstanding amount.
     *
     * @param AlchemyBagSlot $alchemyBagSlot
     * @param int $remainingAmount
     * @return int
     */
    private function consumeFromAlchemyBagSlot(AlchemyBagSlot $alchemyBagSlot, int $remainingAmount): int
    {
        if ($alchemyBagSlot->amount <= $remainingAmount) {
            $alchemyBagSlot->delete();

            return $remainingAmount - $alchemyBagSlot->amount;
        }

        $alchemyBagSlot->update(['amount' => $alchemyBagSlot->amount - $remainingAmount]);

        return 0;
    }

    /**
     * Determine whether active Batch Crafting prevents consumption.
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
