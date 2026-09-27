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
use Illuminate\Support\Collection;

class UseItemService
{
    use ResponseBuilder;

    const MAX_TIME = 8 * 60;

    const MAX_AMOUNT = 10;

    /**
     * @param CharacterActiveBoonService $characterActiveBoonService
     */
    public function __construct(
        private readonly CharacterActiveBoonService $characterActiveBoonService,
    ) {}

    /**
     * Use several selected Alchemy Bag items for the character, applying boons up to the active limits.
     *
     * @param Character $character
     * @param array $itemsToUse
     * @return array
     */
    public function useManyItemsFromInventory(Character $character, array $itemsToUse): array
    {
        $automation = $this->activeAutomation($character);

        $currentBoonCount = $character->boons()->active()->sum('amount_used');

        if ($currentBoonCount >= self::MAX_AMOUNT) {
            return $this->errorResult($this->maximumBoonsMessage());
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
            return $this->errorResult($this->missingInventoryItemMessage());
        }

        if (! $this->requestedAlchemyAmountsAreAvailable(array_count_values($itemsToUse), $alchemySlots)) {
            return $this->errorResult($this->missingInventoryItemMessage());
        }

        if (! is_null($automation) && $alchemySlots->contains(fn ($slot) => ! $this->isAlchemyBoonItem($slot->item))) {
            return $this->errorResult($this->automationItemUseMessage($automation));
        }

        foreach ($itemsToUse as $slotId) {
            $removedSomeItems = ! $this->useRequestedAlchemyItem($character, $slotId) || $removedSomeItems;

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
     *
     * @param Character $character
     * @param AlchemyBagSlot $slot
     * @return array
     */
    public function useSingleAlchemyItem(Character $character, AlchemyBagSlot $slot): array
    {
        if (! $this->ownsAlchemyBagSlot($character, $slot)) {
            return $this->errorResult($this->missingInventoryItemMessage());
        }

        return $this->useSingleItemFromInventory($character, $slot->item);
    }

    /**
     * Use as many stacked units of an owned Alchemy Bag slot item as the boon limits allow.
     *
     * @param Character $character
     * @param AlchemyBagSlot $slot
     * @return array
     */
    public function useAllAlchemyItems(Character $character, AlchemyBagSlot $slot): array
    {
        if (! $this->ownsAlchemyBagSlot($character, $slot)) {
            return $this->errorResult($this->missingInventoryItemMessage());
        }

        if (! $this->isAlchemyBoonItem($slot->item) || ! $slot->item->can_stack || $slot->item->lasts_for <= 0) {
            return $this->errorResult($this->boonUseBlockedMessage());
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
            : intval(ceil(now()->diffInSeconds($foundBoon->complete) / 60));
        $remainingDurationUses = intval(ceil(max(0, self::MAX_TIME - $minutesLeft) / $slot->item->lasts_for));
        $amountToUse = min(self::MAX_AMOUNT, $remainingBoonUses, $remainingDurationUses, $slot->amount);

        if ($amountToUse <= 0) {
            return $this->errorResult($this->boonUseBlockedMessage());
        }

        return $this->useManyItemsFromInventory($character, array_fill(0, $amountToUse, $slot->id));
    }

    /**
     * Use a single selected Alchemy Bag or inventory item for the character.
     *
     * @param Character $character
     * @param Item $item
     * @return array
     */
    public function useSingleItemFromInventory(Character $character, Item $item): array
    {
        $automation = $this->activeAutomation($character);

        if (! is_null($automation) && ! $this->isAlchemyBoonItem($item)) {
            return $this->errorResult($this->automationItemUseMessage($automation));
        }

        $currentBoonCount = $character->boons()->active()->sum('amount_used');

        if ($currentBoonCount >= self::MAX_AMOUNT) {
            return $this->errorResult($this->maximumBoonsMessage());
        }

        $useErrorMessage = $this->useOwnedItem($character, $item);

        if (! is_null($useErrorMessage)) {
            return $this->errorResult($useErrorMessage);
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
     *
     * @param InventorySlot $slot
     * @param Character $character
     * @return bool
     */
    public function useItem(InventorySlot $slot, Character $character): bool
    {
        $foundBoon = $character->boons()
            ->active()
            ->where('item_id', $slot->item_id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (! is_null($foundBoon)) {
            return $this->stackInventoryBoon($slot, $foundBoon);
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
     *
     * @param Character $character
     * @param CharacterBoon $boon
     * @return array
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
            return $this->errorResult($this->boonUseBlockedMessage());
        }

        $minutesLeft = $boon->complete->lessThanOrEqualTo(now()) ? 0 : intval(ceil(now()->diffInSeconds($boon->complete) / 60));
        $maximumDuration = $item->can_stack
            ? min(self::MAX_TIME, $boon->amount_used * $item->lasts_for)
            : min(self::MAX_TIME, $item->lasts_for);
        $missing = $maximumDuration - $minutesLeft;

        if ($missing <= 0) {
            return $this->errorResult($this->boonUseBlockedMessage());
        }

        $needed = intval(ceil($missing / $item->lasts_for));
        $available = $alchemyBagSlot->amount;

        if ($available <= 0) {
            return $this->errorResult('You do not have any more of that item.');
        }

        $used = min($needed, $available);
        $timeAdded = min($missing, $used * $item->lasts_for);

        $this->persistAlchemyBagSlotAmount($alchemyBagSlot, $alchemyBagSlot->amount - $used);

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
     * Apply or stack an Alchemy Bag slot item as a boon on the character, refusing Gem Scrolls and Compensation Caches.
     *
     * @param AlchemyBagSlot $slot
     * @param Character $character
     * @return bool
     */
    private function useAlchemyBagItem(AlchemyBagSlot $slot, Character $character): bool
    {
        if (! is_null($slot->item->gem_scroll_type) || ! is_null($slot->item->currency_cache_type)) {
            return false;
        }

        $foundBoon = $character->boons()
            ->active()
            ->where('item_id', $slot->item_id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (! is_null($foundBoon)) {
            return $this->stackAlchemyBoon($slot, $foundBoon);
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
     *
     * @param AlchemyBagSlot $slot
     * @return void
     */
    private function decrementAlchemyBagSlot(AlchemyBagSlot $slot): void
    {
        if ($slot->amount <= 1) {
            $slot->delete();

            return;
        }

        $slot->update(['amount' => $slot->amount - 1]);
    }

    /**
     * Store the Alchemy Bag slot's new amount, deleting the slot when none remain.
     *
     * @param AlchemyBagSlot $slot
     * @param int $amount
     * @return void
     */
    private function persistAlchemyBagSlotAmount(AlchemyBagSlot $slot, int $amount): void
    {
        if ($amount <= 0) {
            $slot->delete();

            return;
        }

        $slot->update(['amount' => $amount]);
    }

    /**
     * Extend an active boon with one more inventory item, consuming the inventory slot.
     *
     * @param InventorySlot $slot
     * @param CharacterBoon $foundBoon
     * @return bool
     */
    private function stackInventoryBoon(InventorySlot $slot, CharacterBoon $foundBoon): bool
    {
        if (! $slot->item->can_stack || $foundBoon->amount_used >= self::MAX_AMOUNT) {
            return false;
        }

        $minutesLeft = $foundBoon->complete->lessThanOrEqualTo(now()) ? 0 : intval(ceil(now()->diffInSeconds($foundBoon->complete) / 60));
        $newLastsForMinutes = min(self::MAX_TIME, $minutesLeft + $slot->item->lasts_for);

        if ($newLastsForMinutes <= $minutesLeft) {
            return false;
        }

        $foundBoon->update([
            'last_for_minutes' => $newLastsForMinutes,
            'complete' => now()->addMinutes($newLastsForMinutes),
            'amount_used' => min(self::MAX_AMOUNT, $foundBoon->amount_used + 1),
        ]);

        $slot->delete();

        return true;
    }

    /**
     * Extend an active boon with one more Alchemy Bag item, decrementing the Alchemy Bag slot.
     *
     * @param AlchemyBagSlot $slot
     * @param CharacterBoon $foundBoon
     * @return bool
     */
    private function stackAlchemyBoon(AlchemyBagSlot $slot, CharacterBoon $foundBoon): bool
    {
        if (! $slot->item->can_stack || $foundBoon->amount_used >= self::MAX_AMOUNT) {
            return false;
        }

        $minutesLeft = $foundBoon->complete->lessThanOrEqualTo(now()) ? 0 : intval(ceil(now()->diffInSeconds($foundBoon->complete) / 60));
        $newLastsForMinutes = min(self::MAX_TIME, $minutesLeft + $slot->item->lasts_for);

        if ($newLastsForMinutes <= $minutesLeft) {
            return false;
        }

        $foundBoon->update([
            'last_for_minutes' => $newLastsForMinutes,
            'complete' => now()->addMinutes($newLastsForMinutes),
            'amount_used' => min(self::MAX_AMOUNT, $foundBoon->amount_used + 1),
        ]);

        $this->decrementAlchemyBagSlot($slot);

        return true;
    }

    /**
     * Determine whether every requested Alchemy Bag slot holds at least the requested amount.
     *
     * @param array $requestedAmounts
     * @param Collection $alchemySlots
     * @return bool
     */
    private function requestedAlchemyAmountsAreAvailable(array $requestedAmounts, Collection $alchemySlots): bool
    {
        return collect($requestedAmounts)->every(fn (int $requestedAmount, int $slotId) => $requestedAmount <= $alchemySlots->get($slotId)->amount);
    }

    /**
     * Use one requested Alchemy Bag slot item, returning whether it was applied.
     *
     * @param Character $character
     * @param int $slotId
     * @return bool
     */
    private function useRequestedAlchemyItem(Character $character, int $slotId): bool
    {
        $alchemySlot = $character->alchemyBag->slots()->find($slotId);

        if (is_null($alchemySlot)) {
            return false;
        }

        return $this->useAlchemyBagItem($alchemySlot, $character);
    }

    /**
     * Use the owned Alchemy Bag or inventory copy of the Item, returning an error message on failure.
     *
     * @param Character $character
     * @param Item $item
     * @return ?string
     */
    private function useOwnedItem(Character $character, Item $item): ?string
    {
        if ($item->type === 'alchemy') {
            return $this->useOwnedAlchemyItem($character, $item);
        }

        return $this->useOwnedInventoryItem($character, $item);
    }

    /**
     * Use the Character's Alchemy Bag copy of the Item, returning an error message on failure.
     *
     * @param Character $character
     * @param Item $item
     * @return ?string
     */
    private function useOwnedAlchemyItem(Character $character, Item $item): ?string
    {
        $foundSlot = $character->alchemyBag?->slots()->where('item_id', $item->id)->first();

        if (is_null($foundSlot)) {
            return $this->missingInventoryItemMessage();
        }

        if (! $this->useAlchemyBagItem($foundSlot, $character)) {
            return $this->boonUseBlockedMessage();
        }

        return null;
    }

    /**
     * Use the Character's inventory copy of the Item, returning an error message on failure.
     *
     * @param Character $character
     * @param Item $item
     * @return ?string
     */
    private function useOwnedInventoryItem(Character $character, Item $item): ?string
    {
        $foundSlot = $character->inventory->slots->first(fn (InventorySlot $slot) => $slot->item_id === $item->id);

        if (is_null($foundSlot)) {
            return $this->missingInventoryItemMessage();
        }

        if (! $this->useItem($foundSlot, $character)) {
            return $this->boonUseBlockedMessage();
        }

        return null;
    }

    /**
     * Build the message for a selected item the character does not have.
     *
     * @return string
     */
    private function missingInventoryItemMessage(): string
    {
        return 'Could not find the selected items you wanted to use in your inventory. Are you sure you have them?';
    }

    /**
     * Build the message for an item that cannot be applied as a boon right now.
     *
     * @return string
     */
    private function boonUseBlockedMessage(): string
    {
        return 'Cannot use requested item. Items may stack to a multiple of 10 or a max of 8 hours. Non stacking items cannot be used more then once, while another one is running.';
    }

    /**
     * Build the message for a character who already has the maximum number of boons.
     *
     * @return string
     */
    private function maximumBoonsMessage(): string
    {
        return 'You can only have a maximum of ten boons applied. Check active boons to see which ones you have. You can always cancel one by clicking on the row.';
    }

    /**
     * Determine whether the character owns the given Alchemy Bag slot.
     *
     * @param Character $character
     * @param AlchemyBagSlot $slot
     * @return bool
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
     *
     * @param Character $character
     * @return ?CharacterAutomation
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
     * Determine whether the item is a usable Alchemy boon item rather than a Gem Scroll or Compensation Cache.
     *
     * @param Item $item
     * @return bool
     */
    public function isAlchemyBoonItem(Item $item): bool
    {
        return $item->type === 'alchemy'
            && $item->usable
            && $item->lasts_for > 0
            && ! $item->damages_kingdoms
            && ! $item->can_use_on_other_items
            && is_null($item->gem_scroll_type)
            && is_null($item->currency_cache_type);
    }

    /**
     * Build the player-facing message explaining why item use is blocked by an active automation.
     *
     * @param CharacterAutomation $automation
     * @return string
     */
    private function automationItemUseMessage(CharacterAutomation $automation): string
    {
        return 'No you are busy, you can use Alchemy items that apply boons to your character. Please cancel your: '.$this->automationName($automation).', if you want to use this.';
    }

    /**
     * Resolve the player-facing name for the given automation's type.
     *
     * @param CharacterAutomation $automation
     * @return string
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
     *
     * @param Character $character
     * @return void
     */
    private function broadcastCharacterBoons(Character $character): void
    {
        event(new CharacterBoonsUpdateBroadcastEvent($character->user, $this->characterActiveBoonService->activeBoons($character)));
    }

    /**
     * Remove a boon from the character and queue the Character recalculation.
     *
     * @param Character $character
     * @param CharacterBoon $boon
     * @return void
     */
    public function removeBoon(Character $character, CharacterBoon $boon): void
    {
        $boon->delete();

        $this->refreshCharacterAfterBoonChange($character);
    }

    /**
     * Refresh the character, queue the Character recalculation, and broadcast active boons.
     *
     * @param Character $character
     * @return void
     */
    public function refreshCharacterAfterBoonChange(Character $character): void
    {
        $character = $character->refresh();

        CharacterAttackTypesCacheBuilder::dispatch($character);

        $this->broadcastCharacterBoons($character);
    }
}
