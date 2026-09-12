<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\CharacterBoon;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Game\Automation\Values\AutomationType;
use App\Game\Character\Builders\AttackBuilders\Jobs\CharacterAttackTypesCacheBuilder;
use App\Game\Character\CharacterInventory\Events\CharacterBoonsUpdateBroadcastEvent;
use App\Game\Character\CharacterInventory\Jobs\CharacterBoonJob;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use App\Game\Core\Traits\ResponseBuilder;

class UseItemService
{
    use ResponseBuilder;

    const MAX_TIME = 8 * 60;

    const MAX_AMOUNT = 10;

    public function __construct(
        private readonly CharacterActiveBoonService $characterActiveBoonService,
    ) {}

    /**
     * Use several selected Alchemy Bag items for the character, applying boons up to the active limits.
     */
    public function useManyItemsFromInventory(Character $character, array $itemsToUse): array
    {
        $automation = $this->activeAutomation($character);

        $currentBoonCount = $character->boons()->active()->sum('amount_used');

        if ($currentBoonCount >= self::MAX_AMOUNT) {
            return $this->errorResult('You can only have a maximum of ten boons applied. Check active boons to see which ones you have. You can always cancel one by clicking on the row.');
        }

        $removedSomeItems = false;
        $possibleNewBoonCount = $currentBoonCount + count($itemsToUse);
        $itemsToRemove = max(0, $possibleNewBoonCount - self::MAX_AMOUNT);

        if ($possibleNewBoonCount > self::MAX_AMOUNT) {
            $itemsToUse = array_slice($itemsToUse, 0, -$itemsToRemove);
            $removedSomeItems = true;
        }

        $uniqueIds = array_unique($itemsToUse);
        $alchemySlots = $character->alchemyBag
            ? $character->alchemyBag->slots()->whereIn('id', $uniqueIds)->get()->keyBy('id')
            : collect();

        if ($alchemySlots->count() !== count($uniqueIds)) {
            return $this->errorResult('Could not find the selected items you wanted to use in your inventory. Are you sure you have them?');
        }

        $requestedAmounts = array_count_values($itemsToUse);

        foreach ($requestedAmounts as $slotId => $requestedAmount) {
            if ($requestedAmount > $alchemySlots->get($slotId)->amount) {
                return $this->errorResult('Could not find the selected items you wanted to use in your inventory. Are you sure you have them?');
            }
        }

        if (! is_null($automation) && $alchemySlots->contains(fn ($slot) => ! $this->isAlchemyBoonItem($slot->item))) {
            return $this->errorResult($this->automationItemUseMessage($automation));
        }

        foreach ($itemsToUse as $slotId) {
            $alchemySlot = $character->alchemyBag->slots()->find($slotId);

            if (is_null($alchemySlot) || ! $this->useAlchemyBagItem($alchemySlot, $character)) {
                $removedSomeItems = true;
            }

            $character = $character->refresh();
        }

        $character = $character->refresh();

        CharacterAttackTypesCacheBuilder::dispatch($character);

        event(new UpdateCharacterInventoryCountEvent($character));

        $this->broadcastCharacterBoons($character);

        return $this->successResult([
            'message' => 'Used selected items.'.($removedSomeItems ? ' Some items were not able to be used because of the amount of boons you have. You can check your Alchemy Bag to see which ones are left.' : ''),
        ]);
    }

    /**
     * Use a single Alchemy Bag slot item owned by the character.
     */
    public function useSingleAlchemyItem(Character $character, AlchemyBagSlot $slot): array
    {
        if (! $this->ownsAlchemyBagSlot($character, $slot)) {
            return $this->errorResult('Could not find the selected items you wanted to use in your inventory. Are you sure you have them?');
        }

        return $this->useSingleItemFromInventory($character, $slot->item);
    }

    /**
     * Use as many stacked units of an owned Alchemy Bag slot item as the boon limits allow.
     */
    public function useAllAlchemyItems(Character $character, AlchemyBagSlot $slot): array
    {
        if (! $this->ownsAlchemyBagSlot($character, $slot)) {
            return $this->errorResult('Could not find the selected items you wanted to use in your inventory. Are you sure you have them?');
        }

        if (! $this->isAlchemyBoonItem($slot->item) || ! $slot->item->can_stack || $slot->item->lasts_for <= 0) {
            return $this->errorResult(
                'Cannot use requested item. Items may stack to a multiple of 10 or a max of 8 hours. Non stacking items cannot be used more then once, while another one is running.'
            );
        }

        $currentBoonCount = $character->boons()->active()->sum('amount_used');
        $remainingBoonUses = self::MAX_AMOUNT - $currentBoonCount;
        $foundBoon = $character->boons()
            ->active()
            ->where('item_id', $slot->item_id)
            ->where('last_for_minutes', '<=', self::MAX_TIME)
            ->orderBy('created_at', 'desc')
            ->first();
        $minutesLeft = is_null($foundBoon) || $foundBoon->complete->lessThanOrEqualTo(now())
            ? 0
            : (int) ceil(now()->diffInSeconds($foundBoon->complete) / 60);
        $remainingDurationUses = (int) ceil(max(0, self::MAX_TIME - $minutesLeft) / $slot->item->lasts_for);
        $amountToUse = min(self::MAX_AMOUNT, $remainingBoonUses, $remainingDurationUses, $slot->amount);

        if ($amountToUse <= 0) {
            return $this->errorResult(
                'Cannot use requested item. Items may stack to a multiple of 10 or a max of 8 hours. Non stacking items cannot be used more then once, while another one is running.'
            );
        }

        return $this->useManyItemsFromInventory($character, array_fill(0, $amountToUse, $slot->id));
    }

    /**
     * Use a single selected Alchemy Bag or inventory item for the character.
     */
    public function useSingleItemFromInventory(Character $character, Item $item): array
    {
        $automation = $this->activeAutomation($character);

        if (! is_null($automation) && ! $this->isAlchemyBoonItem($item)) {
            return $this->errorResult($this->automationItemUseMessage($automation));
        }

        $currentBoonCount = $character->boons()->active()->sum('amount_used');

        if ($currentBoonCount >= self::MAX_AMOUNT) {
            return $this->errorResult('You can only have a maximum of ten boons applied. Check active boons to see which ones you have. You can always cancel one by clicking on the row.');
        }

        if ($item->type === 'alchemy') {
            $foundSlot = $character->alchemyBag?->slots()->where('item_id', $item->id)->first();

            if (is_null($foundSlot)) {
                return $this->errorResult('Could not find the selected items you wanted to use in your inventory. Are you sure you have them?');
            }

            if (! $this->useAlchemyBagItem($foundSlot, $character)) {
                return $this->errorResult(
                    'Cannot use requested item. Items may stack to a multiple of 10 or a max of 8 hours. Non stacking items cannot be used more then once, while another one is running.'
                );
            }
        } else {
            $foundSlot = $character->inventory->slots->filter(function ($slot) use ($item) {
                return $slot->item_id === $item->id;
            })->first();

            if (is_null($foundSlot)) {
                return $this->errorResult('Could not find the selected items you wanted to use in your inventory. Are you sure you have them?');
            }

            if (! $this->useItem($foundSlot, $character)) {
                return $this->errorResult(
                    'Cannot use requested item. Items may stack to a multiple of 10 or a max of 8 hours. Non stacking items cannot be used more then once, while another one is running.'
                );
            }
        }

        $character = $character->refresh();

        CharacterAttackTypesCacheBuilder::dispatch($character);

        event(new UpdateCharacterInventoryCountEvent($character));

        $this->broadcastCharacterBoons($character);

        return $this->successResult([
            'message' => 'Used selected item.',
        ]);
    }

    /**
     * Use the item on the character and create a boon.
     */
    public function useItem(InventorySlot $slot, Character $character): bool
    {
        $foundBoon = $character->boons()
            ->active()
            ->where('item_id', $slot->item_id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (! is_null($foundBoon)) {
            if (! $slot->item->can_stack) {
                return false;
            }

            if ($foundBoon->amount_used >= self::MAX_AMOUNT) {
                return false;
            }

            $minutesLeft = $foundBoon->complete->lessThanOrEqualTo(now()) ? 0 : (int) ceil(now()->diffInSeconds($foundBoon->complete) / 60);
            $newLastsForMinutes = min(self::MAX_TIME, $minutesLeft + $slot->item->lasts_for);

            if ($newLastsForMinutes <= $minutesLeft) {
                return false;
            }

            $timeStamp = now()->addMinutes($newLastsForMinutes);
            $amountUsed = min(self::MAX_AMOUNT, $foundBoon->amount_used + 1);

            $foundBoon->update([
                'last_for_minutes' => $newLastsForMinutes,
                'complete' => $timeStamp,
                'amount_used' => $amountUsed,
            ]);

            $slot->delete();

            return true;
        }

        $completedAt = now()->addMinutes($slot->item->lasts_for);

        $boon = $character->boons()->create([
            'character_id' => $character->id,
            'item_id' => $slot->item->id,
            'started' => now(),
            'complete' => $completedAt,
            'amount_used' => 1,
            'last_for_minutes' => $slot->item->lasts_for,
        ]);

        CharacterBoonJob::dispatch($boon->id)->delay($completedAt);

        $slot->delete();

        return true;
    }

    /**
     * Extend an active boon's remaining duration using available Alchemy Bag stock of its item.
     */
    public function fillUpBoon(Character $character, CharacterBoon $boon): array
    {
        $boon = $character->boons()->active()->find($boon->id);

        if (is_null($boon)) {
            return $this->errorResult('This boon is no longer active.');
        }

        $alchemyBagSlot = AlchemyBagSlot::where('alchemy_bag_id', $character->alchemyBag?->id)
            ->where('character_id', $character->id)
            ->where('item_id', $boon->item_id)
            ->first();

        if (is_null($alchemyBagSlot)) {
            return $this->errorResult('You do not have any more of that item.');
        }

        $item = Item::find($boon->item_id);

        if (is_null($item)) {
            return $this->errorResult('You do not have any more of that item.');
        }

        if (! $item->can_stack && $boon->amount_used !== 1) {
            return $this->errorResult(
                'Cannot use requested item. Items may stack to a multiple of 10 or a max of 8 hours. Non stacking items cannot be used more then once, while another one is running.'
            );
        }

        $minutesLeft = $boon->complete->lessThanOrEqualTo(now()) ? 0 : (int) ceil(now()->diffInSeconds($boon->complete) / 60);
        $maximumDuration = $item->can_stack
            ? min(self::MAX_TIME, $boon->amount_used * $item->lasts_for)
            : min(self::MAX_TIME, $item->lasts_for);
        $missing = $maximumDuration - $minutesLeft;

        if ($missing <= 0) {
            return $this->errorResult(
                'Cannot use requested item. Items may stack to a multiple of 10 or a max of 8 hours. Non stacking items cannot be used more then once, while another one is running.'
            );
        }

        $needed = (int) ceil($missing / $item->lasts_for);
        $available = $alchemyBagSlot->amount;

        if ($available <= 0) {
            return $this->errorResult('You do not have any more of that item.');
        }

        $used = min($needed, $available);

        if ($used <= 0) {
            return $this->errorResult('You do not have any more of that item.');
        }

        $timeAdded = min($missing, $used * $item->lasts_for);

        $newAmount = max(0, $alchemyBagSlot->amount - $used);

        if ($newAmount === 0) {
            $alchemyBagSlot->delete();
        } else {
            $alchemyBagSlot->update(['amount' => $newAmount]);
        }

        $boon->update([
            'complete' => now()->addMinutes($minutesLeft + $timeAdded),
            'last_for_minutes' => $minutesLeft + $timeAdded,
        ]);

        event(new UpdateCharacterInventoryCountEvent($character));

        $this->refreshCharacterAfterBoonChange($character);

        return $this->successResult([
            'message' => $item->name.' filled up using '.$used.' item(s), adding '.$timeAdded.' minutes.',
            'boons' => $character->boons()->active()->get(),
        ]);
    }

    /**
     * Apply or stack an Alchemy Bag slot item as a boon on the character.
     */
    private function useAlchemyBagItem(AlchemyBagSlot $slot, Character $character): bool
    {
        if (! is_null($slot->item->gem_scroll_type)) {
            return false;
        }

        $foundBoon = $character->boons()
            ->active()
            ->where('item_id', $slot->item_id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (! is_null($foundBoon)) {
            if (! $slot->item->can_stack) {
                return false;
            }

            if ($foundBoon->amount_used >= self::MAX_AMOUNT) {
                return false;
            }

            $minutesLeft = $foundBoon->complete->lessThanOrEqualTo(now()) ? 0 : (int) ceil(now()->diffInSeconds($foundBoon->complete) / 60);
            $newLastsForMinutes = min(self::MAX_TIME, $minutesLeft + $slot->item->lasts_for);

            if ($newLastsForMinutes <= $minutesLeft) {
                return false;
            }

            $timeStamp = now()->addMinutes($newLastsForMinutes);
            $amountUsed = min(self::MAX_AMOUNT, $foundBoon->amount_used + 1);

            $foundBoon->update([
                'last_for_minutes' => $newLastsForMinutes,
                'complete' => $timeStamp,
                'amount_used' => $amountUsed,
            ]);

            $this->decrementAlchemyBagSlot($slot);

            return true;
        }

        $lastsForMinutes = min(self::MAX_TIME, $slot->item->lasts_for);
        $completedAt = now()->addMinutes($lastsForMinutes);

        $boon = $character->boons()->create([
            'character_id' => $character->id,
            'item_id' => $slot->item->id,
            'started' => now(),
            'complete' => $completedAt,
            'amount_used' => 1,
            'last_for_minutes' => $lastsForMinutes,
        ]);

        CharacterBoonJob::dispatch($boon->id)->delay($completedAt);

        $this->decrementAlchemyBagSlot($slot);

        return true;
    }

    /**
     * Reduce an Alchemy Bag slot's amount by one, deleting the slot when it reaches zero.
     */
    private function decrementAlchemyBagSlot(AlchemyBagSlot $slot): void
    {
        if ($slot->amount <= 1) {
            $slot->delete();
        } else {
            $slot->update(['amount' => $slot->amount - 1]);
        }
    }

    /**
     * Determine whether the character owns the given Alchemy Bag slot.
     */
    private function ownsAlchemyBagSlot(Character $character, AlchemyBagSlot $slot): bool
    {
        return ! is_null($character->alchemyBag)
            && $slot->alchemy_bag_id === $character->alchemyBag->id
            && $slot->character_id === $character->id
            && $slot->amount > 0;
    }

    /**
     * Resolve the character's currently active automation, if any.
     */
    private function activeAutomation(Character $character): ?CharacterAutomation
    {
        return $character->currentAutomations()
            ->where('completed_at', '>', now())
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Determine whether the item is a usable Alchemy boon item.
     */
    public function isAlchemyBoonItem(Item $item): bool
    {
        return $item->type === 'alchemy'
            && $item->usable
            && $item->lasts_for > 0
            && ! $item->damages_kingdoms
            && ! $item->can_use_on_other_items
            && is_null($item->gem_scroll_type);
    }

    /**
     * Build the player-facing message explaining why item use is blocked by an active automation.
     */
    private function automationItemUseMessage(CharacterAutomation $automation): string
    {
        return 'No you are busy, you can use Alchemy items that apply boons to your character. Please cancel your: '.$this->automationName($automation).', if you want to use this.';
    }

    /**
     * Resolve the player-facing name for the given automation's type.
     */
    private function automationName(CharacterAutomation $automation): string
    {
        $automationType = AutomationType::from($automation->type);

        if ($automationType->isExploring()) {
            return 'Exploration';
        }

        if ($automationType->isDelve()) {
            return 'Delve';
        }

        return 'Faction Loyalty';
    }

    /**
     * Broadcast the character's currently active boons.
     */
    private function broadcastCharacterBoons(Character $character): void
    {
        event(new CharacterBoonsUpdateBroadcastEvent($character->user, $this->characterActiveBoonService->activeBoons($character)));
    }

    /**
     * Remove a boon from the character and queue the Character recalculation.
     */
    public function removeBoon(Character $character, CharacterBoon $boon): void
    {
        $boon->delete();

        $this->refreshCharacterAfterBoonChange($character);
    }

    /**
     * Refresh the character, queue the Character recalculation, and broadcast active boons.
     */
    public function refreshCharacterAfterBoonChange(Character $character): void
    {
        $character = $character->refresh();

        CharacterAttackTypesCacheBuilder::dispatch($character);

        $this->broadcastCharacterBoons($character);
    }
}
