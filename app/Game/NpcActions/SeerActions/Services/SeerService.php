<?php

namespace App\Game\NpcActions\SeerActions\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GemBagSlot;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Values\ArmourTypes;
use App\Flare\Values\WeaponTypes;
use App\Game\Core\Gems\Services\GemComparison;
use App\Game\Core\Traits\ResponseBuilder;
use Facades\App\Game\Core\Handlers\DuplicateItemHandler;
use Facades\App\Game\Core\Handlers\HandleGoldBarsAsACurrency;

class SeerService {

    use ResponseBuilder;

    const SOCKET_COST     = 2000;
    const GEM_ATTACH_COST = 500;
    const REMOVE_GEM      = 10;

    /**
     * @var GemComparison $gemComparison
     */
    private GemComparison $gemComparison;

    /**
     * @param GemComparison $gemComparison
     */
    public function __construct(GemComparison $gemComparison) {
        $this->gemComparison = $gemComparison;
    }

    /**
     * Get items that we can assign gems to.
     *
     * @param Character $character
     * @return array
     */
    public function getItems(Character $character): array {
        return array_values(array_filter($character->inventory->slots->whereNotNull('item.socket_count')->whereIn('item.type', [
            WeaponTypes::WEAPON,
            WeaponTypes::STAVE,
            WeaponTypes::BOW,
            WeaponTypes::HAMMER,
            ArmourTypes::SHIELD,
            ArmourTypes::BODY,
            ArmourTypes::SLEEVES,
            ArmourTypes::HELMET,
            ArmourTypes::FEET,
            ArmourTypes::LEGGINGS,
            ArmourTypes::GLOVES
        ])->map(function($slot) {
            if ($slot->item->socket_count > 0) {
                return [
                    'name' => $slot->item->affix_name,
                    'slot_id' => $slot->id,
                    'socket_amount' => $slot->item->socket_count,
                ];
            }
        })->toArray()));
    }

    /**
     * Get gems to attach.
     *
     * @param Character $character
     * @return array
     */
    public function getGems(Character $character): array {
        return array_values($character->gemBag->gemSlots->map(function($slot) {
            return [
                'name'    => $slot->gem->name,
                'amount'  => $slot->amount,
                'tier'    => $slot->gem->tier,
                'slot_id' => $slot->id,
            ];
        })->toArray());
    }

    /**
     * Create Sockets.
     *
     * @param Character $character
     * @param int $inventorySlotId
     * @return array
     */
    public function createSockets(Character $character, int $inventorySlotId): array {
        $slot = $character->inventory->slots->find($inventorySlotId);

        if (is_null($slot)) {
            return $this->errorResult('No item was found to apply sockets to.');
        }

        if (!HandleGoldBarsAsACurrency::hasTheGoldBars($character->kingdoms, self::SOCKET_COST)) {
            return $this->errorResult('You do not have the gold bars to do this.');
        }

        if ($slot->item->sockets->count() > 0) {
            return $this->errorResult('Cannot re-roll sockets as this item has gems attached. Remove them first.');
        }

        $oldSocketCount = $slot->item->socket_count;

        $this->assignSocketCount($slot);

        $newSocketCount = $slot->refresh()->item->socket_count;

        $character = $character->refresh();

        HandleGoldBarsAsACurrency::subtractCostFromKingdoms($character->kingdoms, self::SOCKET_COST);

        return $this->successResult([
            'items'   => $this->getItems($character),
            'gems'    => $this->getGems($character),
            'message' => 'Attached sockets to item! (Old Socket Count: '.$oldSocketCount.', New Count: '.$newSocketCount.').'
        ]);

    }

    /**
     * Fetch Gems With items For Removal.
     *
     * @param Character $character
     * @return array
     */
    public function fetchGemsWithItemsForRemoval(Character $character): array {
        $items =  $this->getItems($character);
        $gems  = [];

        foreach ($items as $item) {
            $socketWithItem = InventorySlot::where('inventory_id', $character->inventory->id)->where('id', $item['slot_id'])->first();

            $gems[] = [
                'slot_id' => $item['slot_id'],
                'gems'    => $socketWithItem->item->sockets->map(function ($socket) {
                    return [
                        'gem_name'  => $socket->gem->name,
                        'gem_id'    => $socket->gem_id,
                    ];
                }),
                'comparison' => $this->gemComparison->ifItemGemsAreRemoved($socketWithItem->item)
            ];
        }

        return $this->successResult([
            'items' => $items,
            'gems'  => $gems,
        ]);
    }

    /**
     * Remove Gems From Item.
     *
     * @param Character $character
     * @param int $inventorySlotId
     * @param int $gemId
     * @return array
     */
    public function removeGem(Character $character, int $inventorySlotId, int $gemId): array {
        $slot    = $character->inventory->slots->find($inventorySlotId);

        $validationResult = $this->gemRemovalValidation($character, $slot);

        if ($validationResult['status'] !== 200) {
            return $validationResult;
        }

        if (!HandleGoldBarsAsACurrency::hasTheGoldBars($character->kingdoms, self::REMOVE_GEM)) {
            return $this->errorResult('You do not have the gold bars to do this.');
        }

        $slot = $this->removeGemFromItem($character, $slot, $gemId);

        if (is_null($slot)) {
            return $this->errorResult('Item does not have specified gem.');
        }

        $character = $character->refresh();

        $result = $this->fetchGemsWithItemsForRemoval($character);

        return $this->successResult([
            'items'        => $this->getItems($character),
            'gems'         => $this->getGems($character),
            'removal_data' => [
                'items' => $result['items'],
                'gems'  => $result['gems'],
            ],
            'message'       => 'Gem has been removed from the socket!',
        ]);
    }

    /**
     * Remove all gems from the item.
     *
     * @param Character $character
     * @param int $slotId
     * @return array
     */
    public function removeAllGems(Character $character, int $slotId): array {
        $slot    = $character->inventory->slots->find($slotId);

        $validationResult = $this->gemRemovalValidation($character, $slot);

        if ($validationResult['status'] !== 200) {
            return $validationResult;
        }

        $inventoryCount = $character->getInventoryCount() + $slot->item->sockets->count();

        if ($inventoryCount > $character->inventory_max) {
            return $this->errorResult('Not enough room in your inventory to remove all the gems on this item. (gem bag counts).');
        }

        $socketCount = $slot->item->sockets->count();

        if (!HandleGoldBarsAsACurrency::hasTheGoldBars($character->kingdoms, self::REMOVE_GEM * $socketCount)) {
            return $this->errorResult('You do not have the gold bars to do this.');
        }

        foreach ($slot->item->sockets as $socket) {
            $slot = $this->removeGemFromItem($character, $slot, $socket->gem_id);
        }

        $character = $character->refresh();

        $result = $this->fetchGemsWithItemsForRemoval($character);

        return $this->successResult([
            'items'        => $this->getItems($character),
            'gems'         => $this->getGems($character),
            'removal_data' => [
                'items' => $result['items'],
                'gems'  => $result['gems'],
            ],
            'message' => 'All gems have been removed!',
        ]);
    }

    /**
     * Replace the gem at the gem slot specified.
     *
     * @param Character $character
     * @param int $slotId
     * @param int $gemSlotId
     * @param int $gemIdToReplace
     * @return array
     */
    public function replaceGem(Character $character, int $slotId, int $gemSlotId, int $gemIdToReplace): array {
        $slot    = $character->inventory->slots->find($slotId);
        $gemSlot = $character->gemBag->gemSlots->find($gemSlotId);

        if (is_null($slot)) {
            return $this->errorResult('No item was found to replace gem on.');
        }

        if (is_null($gemSlot)) {
            return $this->errorResult('The gem you want to use to replace the requested gem with, does not exist.');
        }

        if ($slot->item->sockets->isEmpty()) {
            return $this->errorResult('The item does not have any sockets. What are you doing?');
        }

        if ($character->isInventoryFull()) {
            return $this->errorResult('Your inventory is full (gem bag counts). Could not replace the gem.');
        }

        if (!HandleGoldBarsAsACurrency::hasTheGoldBars($character->kingdoms, self::REMOVE_GEM)) {
            return $this->errorResult('You do not have the gold bars to do this.');
        }

        $newItem = DuplicateItemHandler::duplicateItem($slot->item);

        $socket = $newItem->sockets->where('gem_id', $gemIdToReplace)->first();

        if (is_null($socket)) {
            return $this->errorResult('No Gem found on the item for the gem you want to replace.');
        }

        $gemInBag = $character->gemBag->gemSlots->where('gem_id', $socket->gem_id)->first();

        if (!is_null($gemInBag)) {
            $gemInBag->update(['amount' => $gemInBag->amount + 1]);
        } else {
            $character->gemBag->gemSlots()->create([
                'gem_bag_id' => $character->gemBag->id,
                'gem_id'     => $socket->gem_id,
                'amount'     => 1,
            ]);
        }

        $socket->update([
            'gem_id' => $gemSlot->gem_id
        ]);

        HandleGoldBarsAsACurrency::subtractCostFromKingdoms($character->kingdoms, self::REMOVE_GEM);

        $slot->update(['item_id' => $newItem->id]);

        $gemSlot->delete();

        $character = $character->refresh();

        return $this->successResult([
            'items'   => $this->getItems($character),
            'gems'    => $this->getGems($character),
            'message' => 'Gem has been replaced!'
        ]);
    }

    /**
     * Assign the gem to a socket.
     *
     * @param Character $character
     * @param int $inventorySlotId
     * @param int $gemSlotId
     * @return array
     */
    public function assignGemToSocket(Character $character, int $inventorySlotId, int $gemSlotId) {
        $slot    = $character->inventory->slots->find($inventorySlotId);
        $gemSlot = $character->gemBag->gemSlots->find($gemSlotId);

        if (is_null($slot)) {
            return $this->errorResult('No item was found to add a gem to.');
        }

        if (is_null($gemSlot)) {
            return $this->errorResult('No gem to attach to supplied item was found.');
        }

        if ($slot->item->socket_count < 1) {
            return $this->errorResult('No Sockets on the supplied item. You need to add sockets to the item first.');
        }

        if ($slot->item->sockets->isNotEmpty() && $slot->item->sockets->count() >= $slot->item->socket_count) {
            return $this->errorResult(('Not enough sockets for this gem.'));
        }

        if (!HandleGoldBarsAsACurrency::hasTheGoldBars($character->kingdoms, self::GEM_ATTACH_COST)) {
            return $this->errorResult('You do not have the gold bars to do this.');
        }

        $this->addGemToItem($slot, $gemSlot);

        HandleGoldBarsAsACurrency::subtractCostFromKingdoms($character->kingdoms, self::SOCKET_COST);

        $character = $character->refresh();

        return $this->successResult([
            'items'   => $this->getItems($character),
            'gems'    => $this->getGems($character),
            'message' => 'Attached gem to item!'
        ]);
    }

    /**
     * Gem removal validation.
     *
     * - Common validation for removing gems.
     *
     * @param Character $character
     * @param InventorySlot|null $slot
     * @return array
     */
    protected function gemRemovalValidation(Character $character, ?InventorySlot $slot = null): array {
        if (is_null($slot)) {
            return $this->errorResult('No item was found to removed gem from.');
        }

        if (is_null($slot->item->socket_count) || $slot->item->socket_count <= 0) {
            return $this->errorResult('No sockets to remove gem from.');
        }

        if ($slot->item->sockets->isEmpty()) {
            return $this->errorResult('Sockets on this item are already empty.');
        }

        if ($character->isInventoryFull()) {
            return $this->errorResult('Your inventory is full (gem bag counts).');
        }

        return $this->successResult();
    }

    /**
     * @param InventorySlot $slot
     * @param GemBagSlot $gemSlot
     * @return Item
     */
    protected function addGemToItem(InventorySlot $slot, GemBagSlot $gemSlot): Item {
        $newItem = DuplicateItemHandler::duplicateItem($slot->item);

        $newItem->sockets()->create([
            'item_id' => $slot->item_id,
            'gem_id'  => $gemSlot->gem_id,
        ]);

        $slot->update([
            'item_id' => $newItem->id,
        ]);

        if ($gemSlot->amount > 1) {
            $gemSlot->update([
                'amount' => $gemSlot->amount - 1
            ]);
        } else {
            $gemSlot->delete();
        }

        return $newItem->refresh();
    }

    /**
     * Remove the gem from the item.
     *
     * @param Character $character
     * @param InventorySlot $slot
     * @param int $gemId
     * @return InventorySlot|null
     */
    protected function removeGemFromItem(Character $character, InventorySlot $slot, int $gemId): InventorySlot|null {
        $newItem = DuplicateItemHandler::duplicateItem($slot->item);

        $socket = $newItem->sockets->where('gem_id', $gemId)->first();

        if (is_null($socket)) {
            return null;
        }

        $gemInBag = $character->gemBag->gemSlots->where('gem_id', $socket->gem_id)->first();

        if (!is_null($gemInBag)) {
            $gemInBag->update(['amount' => $gemInBag->amount + 1]);
        } else {
            $character->gemBag->gemSlots()->create([
                'gem_bag_id' => $character->gemBag->id,
                'gem_id'     => $socket->gem_id,
                'amount'     => 1,
            ]);
        }

        $socket->delete();

        HandleGoldBarsAsACurrency::subtractCostFromKingdoms($character->kingdoms, self::REMOVE_GEM);

        $slot->update(['item_id' => $newItem->id]);

        return $slot->refresh();
    }

    /**
     * Get random type.
     *
     * @return int
     */
    protected function getRandomType(): int {
        return rand(1, 100);
    }

    /**
     * Assign a random socket count (1-6 sockets)
     *
     * @param InventorySlot $slot
     * @return void
     */
    protected function assignSocketCount(InventorySlot $slot): void {

        $newItem = DuplicateItemHandler::duplicateItem($slot->item);

        $type = $this->getRandomType();

        if ($type > 99) {
            $newItem->update(['socket_count' => 6]);

            $slot->update([
                'item_id' => $newItem->refresh()->id,
            ]);

            return;
        }

        if ($type >= 95) {
            $newItem->update(['socket_count' => 5]);

            $slot->update([
                'item_id' => $newItem->refresh()->id,
            ]);

            return;
        }

        if ($type >= 80) {
            $newItem->update(['socket_count' => 4]);

            $slot->update([
                'item_id' => $newItem->refresh()->id,
            ]);

            return;
        }

        if ($type >= 60) {
            $newItem->update(['socket_count' => 3]);

            $slot->update([
                'item_id' => $newItem->refresh()->id,
            ]);

            return;
        }

        if ($type >= 50) {
            $newItem->update(['socket_count' => 2]);

            $slot->update([
                'item_id' => $newItem->refresh()->id,
            ]);

            return;
        }

        if ($type >= 1) {
            $newItem->update(['socket_count' => 1]);
        }

        $slot->update([
            'item_id' => $newItem->refresh()->id,
        ]);
    }
}
