<?php

namespace App\Game\Core\Services;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Values\ArmourTypes;
use App\Flare\Values\WeaponTypes;
use App\Game\Core\Traits\ResponseBuilder;
use Facades\App\Game\Core\Handlers\DuplicateItemHandler;
use Facades\App\Game\Core\Handlers\HandleGoldBarsAsACurrency;

class SeerService {

    use ResponseBuilder;

    const SOCKET_COST     = 2000;
    const GEM_ATTACH_COST = 500;
    const REMOVE_GEM      = 10;

    public function createSockets(Character $character, int $inventorySlotId): array {
        $slot = $character->inventory->slots->find($inventorySlotId);

        if (is_null($slot)) {
            return $this->errorResult('No item was found to apply sockets to.');
        }

        if (!HandleGoldBarsAsACurrency::hasTheGoldBars($character->kingdoms, self::SOCKET_COST)) {
            return $this->errorResult('You do not have the gold bars to do this.');
        }

        $this->assignSocketCount($slot);

        $character = $character->refresh();

        HandleGoldBarsAsACurrency::subtractCostFromKingdoms($character->kingdoms, self::SOCKET_COST);

        return $this->successResult([
            'items'   => $this->getItems($character),
            'message' => 'Attached sockets to item!'
        ]);

    }

    public function removeGems(Character $character, int $inventorySlotId, int $gemId) {
        $slot    = $character->inventory->slots->find($inventorySlotId);

        if (is_null($slot)) {
            return $this->errorResult('No item was found to add a gem to.');
        }

        if ($character->isInventoryFull()) {
            return $this->errorResult('Your inventory is full (gem bag counts).');
        }

        if (!HandleGoldBarsAsACurrency::hasTheGoldBars($character->kingdoms, self::REMOVE_GEM)) {
            return $this->errorResult('You do not have the gold bars to do this.');
        }

        if ($slot->item->socket_amount > 0) {
            $socket = $slot->item->sockets->where('gem_id', $gemId)->first();

            if (is_null($socket)) {
                return $this->errorResult('Item does not have specified gem.');
            }

            $gemInBag = $character->gemBag->gemSlots->where('gem_id', $socket->gem_id)->first();

            if (!is_null($gemInBag)) {
                $gemInBag->update(['amount' => $gemInBag->amount + 1]);
            }

            $character->gemBag()->gemSlots()->create([
                'gem_bag_id' => $character->gemBag->id,
                'gem_id'     => $socket->gem_id,
                'amount'     => 1,
            ]);

            $socket->delete();

            HandleGoldBarsAsACurrency::subtractCostFromKingdoms($character->kingdoms, self::REMOVE_GEM);

            $character = $character->refresh();

            return $this->successResult([
                'items'   => $this->getItems($character),
                'gems'    => $this->getGems($character),
                'message' => 'Gem has been removed from the socket!'
            ]);
        }

        return $this->errorResult('Item has no sockets. What are you doing?');
    }

    public function assignGemToSocket(Character $character, int $inventorySlotId, int $gemSlotId) {
        $slot    = $character->inventory->slots->find($inventorySlotId);
        $gemSlot = $character->gemBags->gemSlot->find($gemSlotId);

        if (is_null($slot)) {
            return $this->errorResult('No item was found to add a gem to.');
        }

        if (is_null($gemSlot)) {
            return $this->errorResult('No gem to attach to supplied item.');
        }

        if (!HandleGoldBarsAsACurrency::hasTheGoldBars($character->kingdoms, self::GEM_ATTACH_COST)) {
            return $this->errorResult('You do not have the gold bars to do this.');
        }

        if ($slot->item->socket_amount > 0 && $slot->item->sockets->count < $slot->item->socket_amount) {
            $slot->item->sockets()->create([
                'item_id' => $slot->item_id,
                'gem_id'  => $gemSlot->gem_id,
            ]);
        }

        $gemSlot->delete();

        HandleGoldBarsAsACurrency::subtractCostFromKingdoms($character->kingdoms, self::SOCKET_COST);

        $character = $character->refresh();

        return $this->successResult([
            'items'   => $this->getItems($character),
            'gems'    => $this->getGems($character),
            'message' => 'Attached gem to item!'
        ]);
    }

    public function getItems(Character $character): array {
        return array_values($character->inventory->slots->where('item.socket_amount', '>=', 0)->whereIn('item.type', [
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
            return [
                'name'          => $slot->item->affix_name,
                'slot_id'       => $slot->id,
                'socket_amount' => $slot->item->socket_count,
            ];
        })->toArray());
    }

    public function getGems(Character $character): array {
        return $character->gemBag->gemSlots->pluck('gem.name', 'id')->toArray();
    }

    protected function assignSocketCount(InventorySlot $slot): void {

        $newItem = DuplicateItemHandler::duplicateItem($slot->item);

        $type = rand(1, 100);

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

        if ($type > 1) {
            $newItem->update(['socket_count' => 1]);
        }

        $slot->update([
            'item_id' => $newItem->refresh()->id,
        ]);
    }
}
