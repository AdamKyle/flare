<?php

namespace App\Game\Core\Services;

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
        $equipped = $this->fetchEquipped();

        return [
            'inventory'    => $this->fetchCharacterInventory()->values(),
            'usable_sets'  => $this->getUsableSets(),
            'savable_sets' => $this->getSaveableSets(),
            'equipped'     => !is_null($equipped) ? $equipped : [],
            'sets'         => $this->character->inventorySets()->with(['slots', 'slots.item', 'slots.item.itemPrefix', 'slots.item.itemSuffix'])->get(),
            'quest_items'  => $this->getQuestItems(),
            'usable_items' => $this->getUsableItems(),
        ];
    }

    /**
     * Returns the usable items.
     *
     * @return Collection
     */
    public function getUsableItems(): Collection {
        return $this->character->inventory->slots->filter(function($slot) {
            return $slot->item->usable;
        })->load(['item.itemPrefix', 'item.itemSuffix'])->values();
    }

    /**
     * Returns the quest items.
     *
     * @return Collection
     */
    public function getQuestItems(): Collection {
        return $this->character->inventory->slots->filter(function($slot) {
            return $slot->item->type === 'quest';
        })->load(['item.itemPrefix', 'item.itemSuffix'])->values();
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
        $ids = $this->character->inventorySets()
                    ->where('is_equipped', false)
                    ->pluck('id')
                    ->toArray();

        $indexes = [];

        foreach ($ids as $id) {
            $indexes[] = [
                'index' => $this->character->inventorySets->search(function($set) use($id) {
                        return $set->id === $id;
                    }) + 1,
                'id'    => $id,
                'name'  => $this->character->inventorySets->filter(function($set) use($id) {
                    return $set->id === $id;
                })->first()->name
            ];
        }

        return $indexes;
    }

    /**
     * Gets a list of empty inventory sets to save to.
     *
     * @return array
     */
    public function getSaveableSets(): array {
        $ids = $this->character->inventorySets()
            ->doesntHave('slots')
            ->where('is_equipped', false)
            ->pluck('id')
            ->toArray();

        $indexes = [];

        foreach ($ids as $id) {
            $indexes[] = [
                'index' => $this->character->inventorySets->search(function($set) use($id) {
                    return $set->id === $id;
                }) + 1,
                'id'    => $id,
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
        return $this->character->inventory->slots->filter(function($slot) {
            return !$slot->equipped && !$slot->item->usable && $slot->item->type !== 'quest';
        })->load(['item.itemSuffix', 'item.itemPrefix']);
    }

    public function fetchEquipped(): Collection|InventorySet|null {
        $inventory = $this->character->inventory->slots()->with('item')->get()->filter(function($slot) {
            return $slot->equipped;
        });

        if ($inventory->isNotEmpty()) {
            return $inventory->values();
        }

        return $this->character->inventorySets()
                               ->with(['slots', 'slots.item', 'slots.item.itemSuffix', 'slots.item.itemPrefix'])
                               ->where('is_equipped', true)
                               ->first();
    }

    /**
     * Set the inventory
     *
     * @param Request $request
     * @return CharacterInventoryService
     */
    public function setInventory(string $type): CharacterInventoryService {

        // Bows are considered weapons but have no position as they are duel wielded
        // weapons.
        if ($type === 'weapon' && empty($this->position)) {
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
