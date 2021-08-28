<?php

namespace App\Game\Core\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Flare\Models\Character;
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
