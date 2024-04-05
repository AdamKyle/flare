<?php

namespace App\Game\NpcActions\LabyrinthOracle\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Messages\Events\ServerMessageEvent;
use Facades\App\Game\Core\Handlers\DuplicateItemHandler;
use App\Game\Core\Traits\ResponseBuilder;

class ItemTransferService {

    use ResponseBuilder;

    /**
     * @var Item $itemToTransferFromDuplicated
     */
    private Item $itemToTransferFromDuplicated;

    /**
     * @var Item $itemToTransferToDuplicated
     */
    private Item $itemToTransferToDuplicated;

    /**
     * The Cost of the transfer.
     *
     * @var array|int[]
     */
    private array $currencyCosts = [
        'gold' => 100_000_000,
        'shards' => 5_000,
        'gold_dust' => 2_500,
    ];

    /**
     * @param Character $character
     * @return array
     */
    public function fetchInventoryItems(Character $character): array {

        return array_values($character->refresh()->inventory->slots->filter(function($slot) {
            return !in_array($slot->item->type, [
                'artifact', 'trinket', 'quest', 'alchemy'
            ]);
        })->map(function($slot) {
            return [
                'affix_name' => $slot->item->affix_name,
                'id' => $slot->item_id,
            ];
        })->toArray());
    }

    /**
     * Transfer the enhancements from one item to another.
     *
     * @param Character $character
     * @param int $itemIdToTransferFrom
     * @param int $itemIdToTransferTo
     * @return array
     */
    public function transferItemEnhancements(Character $character, int $itemIdToTransferFrom, int $itemIdToTransferTo): array {

        if (!$this->canAfford($character, $this->currencyCosts)) {
            return $this->errorResult('You cannot afford to do this.');
        }

        $itemSlotToTransferFrom = $character->inventory->slots->where('item_id', $itemIdToTransferFrom)->first();

        $itemSlotToTransferTo = $character->inventory->slots->where('item_id', $itemIdToTransferTo)->first();

        if (is_null($itemSlotToTransferFrom) || is_null($itemIdToTransferTo)) {
            return $this->errorResult('You do not have one of these items.');
        }

        if ($this->cannotTransferFrom($itemSlotToTransferFrom->item)) {
            return $this->errorResult('This item has nothing on it to transfer from.');
        }

        if ($this->cannotMoveGems($character, $itemSlotToTransferTo->item)) {
            return $this->errorResult('You do not have the inventory room to move the gems attached to: ' . $itemSlotToTransferTo->item->affix_name . ' back into your gem bag.');
        }

        $this->itemToTransferFromDuplicated = DuplicateItemHandler::duplicateItem($itemSlotToTransferFrom->item);

        $this->itemToTransferToDuplicated = DuplicateItemHandler::duplicateItem($itemSlotToTransferTo->item);

        $this->removeGemsFromItemToMoveTo($character);

        $character = $character->fresh();

        $this->moveAffixesOver();

        $this->moveHolyStacks();

        $this->moveGems();

        $this->itemToTransferToDuplicated->update([
            'is_mythic' => $this->itemToTransferFromDuplicated->is_mythic,
            'is_cosmic' => $this->itemToTransferFromDuplicated->is_cosmic,
        ]);

        $this->itemToTransferFromDuplicated->update([
            'is_mythic' => false,
            'is_cosmic' => false,
        ]);

        $this->itemToTransferFromDuplicated = $this->itemToTransferFromDuplicated->refresh();
        $this->itemToTransferToDuplicated   = $this->itemToTransferToDuplicated->refresh();

        $itemSlotToTransferFrom->update([
            'item_id' => $this->itemToTransferFromDuplicated->id,
        ]);

        $itemSlotToTransferTo->update([
            'item_id' => $this->itemToTransferToDuplicated->id,
        ]);

        $itemSlotToTransferTo = $itemSlotToTransferTo->refresh();

        event(new ServerMessageEvent($character->user, 'The Labyrinth Oracle works his magic to transfer the magical enhancements to: ' . $this->itemToTransferToDuplicated->affix_name, $itemSlotToTransferTo->id));

        return $this->successResult([
            'message' => 'Transferred attributes (Enchantments, Holy Oils and Gems) from: ' . $this->itemToTransferFromDuplicated->affix_name . ' To: ' . $this->itemToTransferToDuplicated->affix_name . '. Check Server Messages (Mobile: Chat Tabs Drop Down -> Server Messages) for link to new item!',
            'inventory' => $this->fetchInventoryItems($character)
        ]);
    }

    protected function cannotMoveGems(Character $character, Item $item): bool {
        if ($character->isInventoryFull()) {
            return true;
        }

        $totalGemsAttached = $item->sockets->count();

        return ($totalGemsAttached + $character->totalInventoryCount()) > $character->inventory_max;
    }

    /**
     * Can we transfer from this item?
     *Gem
     * These are the rules for being able to transfer items.
     *
     * Item must have either item_prefix or suffix or both.
     * Item must have holy oils, at least one applied.
     * Item must have sockets and/or gems
     *
     * @param Item $itemToTransferFrom
     * @return bool
     */
    protected function cannotTransferFrom(Item $itemToTransferFrom): bool {
        return (
            is_null($itemToTransferFrom->item_prefix_id) &&
            is_null($itemToTransferFrom->item_suffix_id) &&
            $itemToTransferFrom->holy_stacks_applied <= 0 &&
            $itemToTransferFrom->socket_count <= 0
        );

    }

    /**
     * Can the character afford this?
     *
     * @param Character $character
     * @param array $cost
     * @return bool
     */
    protected function canAfford(Character $character, array $cost): bool {
        foreach ($cost as $currencyName => $cost) {
            if ($character->{$currencyName} < $cost) {
                return false;
            }
        }

        return true;
    }

    /**
     * Remove the gems and sockets from the item to move attributes to.
     *
     * @param Character $character
     * @return void
     */
    protected function removeGemsFromItemToMoveTo(Character $character) {
        $socketsCount = $this->itemToTransferToDuplicated->sockets->count();

        if ($socketsCount > 0) {
            foreach ($this->itemToTransferToDuplicated->sockets as $socket) {
                $foundGemSlot = $character->gemBag->gemSlots->where('gem_id', $socket->gem_id)->first();

                if (!is_null($foundGemSlot)) {
                    $foundGemSlot->update([
                        'amount' => $foundGemSlot->amount + 1,
                    ]);
                } else {
                    $character->gemBag->gemSlots()->create([
                        'gem_bag_id' => $character->gemBag->id,
                        'gem_id' => $socket->gem_id,
                        'amount' => 1,
                    ]);
                }
            }

            $this->itemToTransferToDuplicated->sockets()->delete();
            $this->itemToTransferToDuplicated->update([
                'socket_count' => 0
            ]);

            $this->itemToTransferToDuplicated = $this->itemToTransferToDuplicated->fresh();
        }
    }


    /**
     * Move the affixes over.
     *
     * @return void
     */
    protected function moveAffixesOver(): void {
        $this->itemToTransferToDuplicated->update([
            'item_prefix_id' => $this->itemToTransferFromDuplicated->item_prefix_id,
            'item_suffix_id' => $this->itemToTransferFromDuplicated->item_suffix_id,
        ]);

        $this->itemToTransferFromDuplicated->update([
            'item_prefix_id' => null,
            'item_suffix_id' => null,
        ]);

        $this->itemToTransferToDuplicated = $this->itemToTransferToDuplicated->refresh();
        $this->itemToTransferFromDuplicated = $this->itemToTransferFromDuplicated->refresh();
    }

    /**
     * Apply holy stacks from the old item to the new one.
     *
     * Will remove applied holy stacks from one item, if they have them.
     *
     */
    protected function moveHolyStacks(): void
    {
        if ($this->itemToTransferFromDuplicated->appliedHolyStacks()->count() > 0) {

            $totalStacksAllowed = $this->itemToTransferToDuplicated->holy_stacks;
            $currentStacksApplied = 0;

            foreach ($this->itemToTransferFromDuplicated->appliedHolyStacks as $stack) {

                if ($currentStacksApplied === $totalStacksAllowed) {
                    return;
                }

                $stackAttributes = $stack->getAttributes();
                $stackAttributes['item_id'] = $this->itemToTransferToDuplicated->id;

                $newStack = $this->itemToTransferToDuplicated->appliedHolyStacks()->make($stackAttributes);
                $newStack->setRelation('item', $this->itemToTransferToDuplicated);

                $newStack->save();

                $stack->delete();

                $currentStacksApplied++;
            }

            $this->itemToTransferFromDuplicated->appliedHolyStacks()->delete();
        }
    }


    /**
     * Add gems and remove them from the item to transfer from.
     *
     * Will remove gems if the item to move to has them.
     */
    protected function moveGems() {
        if ($this->itemToTransferFromDuplicated->socket_count > 0) {
            foreach ($this->itemToTransferFromDuplicated->sockets as $socket) {
                $newSocket = $this->itemToTransferToDuplicated->sockets()->make([
                    'item_id' => $this->itemToTransferToDuplicated->id,
                    'gem_id'  => $socket->gem_id,
                ]);
                $newSocket->save();
            }
        }

        $this->itemToTransferToDuplicated->update([
            'socket_count' => $this->itemToTransferFromDuplicated->socket_count
        ]);

        $this->itemToTransferFromDuplicated->sockets()->delete();
        $this->itemToTransferFromDuplicated->update([
            'socket_count' => 0
        ]);

        $this->itemToTransferFromDuplicated = $this->itemToTransferFromDuplicated->fresh();
    }

}
