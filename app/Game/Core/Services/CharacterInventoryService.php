<?php

namespace App\Game\Core\Services;

use App\Flare\Models\Inventory;
use App\Flare\Models\SetSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySet;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;

class CharacterInventoryService {

    /**
     * @var Character $character
     */
    private $character;

    /**
     * @var InventorySlot $inventorySlot
     */
    private $inventorySlot;

    /**
     * @var array $positions
     */
    private $positions;

    /**
     * @var bool $isInventorySetIsEquipped
     */
    private bool $isInventorySetIsEquipped = false;

    /**
     * Set the character
     *
     * @param Character $character
     * @return CharacterInventoryService
     */
    public function setCharacter(Character $character): CharacterInventoryService {
        $this->character = $character;

        return $this;
    }

    /**
     * Set the inventory slot
     *
     * @param InventorySlot $inventorySlot
     * @return CharacterInventoryService
     */
    public function setInventorySlot(InventorySlot $inventorySlot): CharacterInventoryService {
        $this->inventorySlot = $inventorySlot;

        return $this;
    }

    /**
     * Set the positions
     *
     * @param array $positions
     * @return CharacterInventoryService
     */
    public function setPositions(array $positions): CharacterInventoryService {
        $this->positions = $positions;

        return $this;
    }

    public function getInventoryForApi(): array {
        $equipped   = $this->fetchEquipped();
        $usableSets = $this->getUsableSets();

        return [
            'inventory'    => $this->fetchCharacterInventory()->values(),
            'usable_sets'  => $usableSets,
            'savable_sets' => $usableSets,
            'equipped'     => !is_null($equipped) ? $equipped : [],
            'sets'         => $this->character->inventorySets()->with(['slots', 'slots.item', 'slots.item.itemPrefix', 'slots.item.itemSuffix'])->get(),
            'quest_items'  => $this->getQuestItems(),
            'usable_items' => $this->getUsableItems(),
            'set_equipped' => $this->isInventorySetIsEquipped,
        ];
    }

    /**
     * @param string $type
     * @return Collection|array
     */
    public function getInventoryForType(string $type): Collection|array {
        switch($type) {
            case 'inventory':
                return $this->fetchCharacterInventory()->values();
            case 'usable_sets':
            case 'savable_sets':
                return $this->getUsableSets();
            case 'equipped':
                $equipped   = $this->fetchEquipped();
                return !is_null($equipped) ? $equipped : [];
            case 'sets':
                return [
                    'sets' => $this->character->inventorySets()->with(['slots', 'slots.item', 'slots.item.itemPrefix', 'slots.item.itemSuffix'])->get(),
                    'set_equipped' => $this->isInventorySetIsEquipped
                ];
            case 'quest_items':
                return $this->getQuestItems();
            case 'usable_items':
                return $this->getUsableItems();
            default:
                return $this->getInventoryForApi();

        }
    }

    /**
     * Returns the usable items.
     *
     * @return Collection
     */
    public function getUsableItems(): Collection {
        $inventory = Inventory::where('character_id', $this->character->id)->first();

        $slots = InventorySlot::where('inventory_slots.inventory_id', $inventory->id)->join('items', function($join) {
            $join->on('inventory_slots.item_id', '=', 'items.id')
                ->where('items.type', 'alchemy');
        })->select('inventory_slots.*')->get();

        return $slots->load('item');
    }

    /**
     * Returns the quest items.
     *
     * @return Collection
     */
    public function getQuestItems(): Collection {
        $inventory = Inventory::where('character_id', $this->character->id)->first();

        $slots = InventorySlot::where('inventory_slots.inventory_id', $inventory->id)->join('items', function($join) {
            $join->on('inventory_slots.item_id', '=', 'items.id')
                ->where('items.type', 'quest');
        })->select('inventory_slots.*')->get();

        return $slots->load('item');
    }

    /**
     * Gets a list of usable slot's
     *
     * We return the index + 1 which refers to the slot number.
     * ie, index of 0, is Slot 1 and so on.
     *
     * @return array
     */
    public function getUsableSets(): array {
        $ids    = InventorySet::where('is_equipped', false)->where('character_id', $this->character->id)->pluck('id')->toArray();
        $setIds = InventorySet::where('character_id', $this->character->id)->pluck('id')->toArray();

        $indexes = [];

        foreach ($ids as $id) {
            $inventorySet = InventorySet::find($id);
            $indexes[] = [
                'index' => array_search($id, $setIds) + 1,
                'id'    => $id,
                'name'  => $inventorySet->name,
            ];
        }

        return $indexes;
    }

    /**
     * Fetches the characters inventory.
     *
     * - Does not include equipped, usable or quest items.
     * - Only comes from inventory, does not include sets.
     *
     * @return Collection
     */
    public function fetchCharacterInventory(): Collection {

        $inventory = Inventory::where('character_id', $this->character->id)->first();

        $slots = InventorySlot::where('inventory_slots.inventory_id', $inventory->id)->join('items', function($join) {
            $join->on('inventory_slots.item_id', '=', 'items.id')
                 ->whereNotIn('items.type', ['quest', 'alchemy']);
        })->where('inventory_slots.equipped', false)->select('inventory_slots.*')->get();

        return $slots->load(['item', 'item.itemSuffix', 'item.itemPrefix']);
    }

    /**
     * Fetch equipped items.
     *
     * @return Collection|InventorySet|null
     */
    public function fetchEquipped(): Collection|InventorySet|null {

        $inventory = Inventory::where('character_id', $this->character->id)->first();

        $slots     = InventorySlot::where('inventory_id', $inventory->id)->where('equipped', true)->with(['item', 'item.itemPrefix', 'item.itemSuffix'])->get();

        if ($slots->isNotEmpty()) {
            return $slots->values();
        }

        $inventorySet = InventorySet::where('character_id', $this->character->id)->where('is_equipped', true)->first();

        if (is_null($inventorySet)) {
            return null;
        }

        $this->isInventorySetIsEquipped = true;

        return SetSlot::where('inventory_set_id', $inventorySet->id)->with(['item', 'item.itemPrefix', 'item.itemSuffix'])->get();
    }

    /**
     * Set the inventory
     *
     * @param Request $request
     * @return CharacterInventoryService
     */
    public function setInventory(string $type): CharacterInventoryService {


        if (in_array($type, ['weapon', 'bow', 'stave', 'hammer']) && empty($this->position)) {
            $this->positions = ['right-hand', 'left-hand'];
        }

        if (empty($this->positions)) {
            $this->inventory =  $this->getInventory($type);

            return $this;
        }

        $this->inventory = $this->getInventory($type, true);

        return $this;
    }

    protected function getInventory(string $type, bool $useArray = false) {
        $inventory = $this->character->inventory->slots->filter(function($slot) use($type, $useArray) {
            if ($useArray) {
                return in_array($slot->position, $this->positions) && $slot->equipped;
            }

            return $slot->item->type === $type && $slot->equipped;
        });

        if ($inventory->isEmpty()) {
            $equippedSet = $this->character->inventorySets()->where('is_equipped', true)->first();

            if (!is_null($equippedSet)) {
                $inventory = $equippedSet->slots->filter(function($slot) use($type, $useArray) {
                    if ($useArray) {
                        return in_array($slot->position, $this->positions) && $slot->equipped;
                    }

                    return $slot->item->type === $type && $slot->equipped;
                });
            }
        }

        return $inventory;
    }

    /**
     * Return the inventory
     *
     * @return Collection
     */
    public function inventory(): Collection {
        return $this->inventory;
    }

    /**
     * Fetches the type of the item.
     *
     * @param Request $request
     * @param Item $item
     * @return string
     */
    public function getType(Item $item, string $type = null): string {
        if (!is_null($type)) {
            return $this->fetchType($type);
        }

        if ($item->type === 'bow') {
            return $item->type;
        }

        return $item->crafting_type;
    }

    protected function fetchType(string $type): string {
        $acceptedTypes = [
            'weapon', 'ring', 'shield', 'artifact', 'spell', 'armour'
        ];

        return in_array($type, $acceptedTypes) ? $type : 'armour';
    }
}
