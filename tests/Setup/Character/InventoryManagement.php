<?php

namespace Tests\Setup\Character;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;

class InventoryManagement {

    private $character;

    private $characterFactory;

    /**
     * Constructor
     *
     * @param Character $character
     */
    public function __construct(Character $character, CharacterFactory $characterFactory = null) {
        $this->character        = $character;
        $this->characterFactory = $characterFactory;
    }

    /**
     * Equip the left hand with an item that isn't already equipped.
     *
     * @param int $slotId | 1
     * @return InventoryManagement
     */
    public function equipLeftHand(string $itemName): InventoryManagement {
        $slot = $this->fetchSlot($itemName);

        $slot->update([
            'equipped' => true,
            'position' => 'left-hand',
        ]);

        $this->character->refresh();

        return $this;
    }

    /**
     * Equip the right hand.
     *
     * @param int $slotId | 1
     * @return InventoryManagement
     */
    public function equipRightHand(string $itemName): InventoryManagement {
        $slot = $this->fetchSlot($itemName);

        $slot->update([
            'equipped' => true,
            'position' => 'right-hand',
        ]);

        $this->character->refresh();

        return $this;
    }

    /**
     * Equip the Spell slot
     *
     * Accepted: spell-one, spell-two
     *
     * @param int $slotId | 1
     * @param string $position | spell-one, spell-two
     * @return InventoryManagement
     */
    public function equipSpellSlot(string $itemName, string $position = 'spell-one'): InventoryManagement {
        $slot = $this->fetchSlot($itemName);

        $slot->update([
            'equipped' => true,
            'position' => $position,
        ]);

        $this->character->refresh();

        return $this;
    }

    /**
     * Equip an artifact.
     *
     * @param int $slotId
     * @param string $position | artifact-one, artifact-two
     * @return InventoryManagement
     */
    public function equipArtifact(string $itemName, string $position = 'artifact-one'): InventoryManagement {
        $slot = $this->fetchSlot($itemName);

        $slot->update([
            'equipped' => true,
            'position' => $position,
        ]);

        $this->character->refresh();

        return $this;
    }

    /**
     * Equip an item.
     *
     * @param int $slotId | 1
     * @param string $position
     * @return InventoryManagement
     */
    public function equipItem(string $position, string $itemName): InventoryManagement {
        $slot = $this->fetchSlot($itemName);

        $slot->update([
            'equipped' => true,
            'position' => $position,
        ]);

        $this->character->refresh();

        return $this;
    }

    /**
     * Give item to the character.
     *
     * Ignores the inventory max limit.
     *
     * @param Item $item
     * @return InventoryManagement
     */
    public function giveItem(Item $item, bool $equip = false, string $position = null): InventoryManagement {
        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id'      => $item->id,
            'equipped'     => $equip,
            'position'     => $position,
        ]);

        $this->character = $this->character->refresh();

        return $this;
    }

    /**
     * Unequip all items.
     *
     * @return InventoryManagement
     */
    public function unequipAll(): InventoryManagement {
        $this->character->inventory->slots->each(function($slot) {
            $slot->update([
                'position' => null,
                'equipped' => false,
            ]);
        });

        $this->character = $this->character->refresh();

        return $this;
    }

    /**
     * Get the character factory.
     *
     * @return CharacterFactory
     */
    public function getCharacterFactory(): CharacterFactory {
        return $this->characterFactory;
    }

    /**
     * Get the character back.
     *
     * @return Character
     */
    public function getCharacter(): Character {
        return $this->character->refresh();
    }

    protected function fetchSlot(string $itemName): InventorySlot {
        $foundMatching = $this->character->inventory->slots->filter(function($slot) use($itemName) {
            return $slot->item->name === $itemName;
         })->first();

         if (is_null($foundMatching)) {
             throw new \Exception('Item is not in inventory or is already equipped');
         }

         return $foundMatching;
    }
}
