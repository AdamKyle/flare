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
    public function equipLeftHand(int $slotId = 1): InventoryManagement {
        $slot = $this->fetchSlot($slotId);

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
    public function equipRightHand(int $slotId = 1): InventoryManagement {
        $slot = $this->fetchSlot($slotId);

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
    public function equipSpellSlot(int $slotId = 1, string $position = 'spell-one'): InventoryManagement {
        $slot = $this->fetchSlot($slotId);

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
    public function equipArtifact(int $slotId = 1, string $position = 'artifact-one'): InventoryManagement {
        $slot = $this->fetchSlot($slotId);

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
    public function equipItem(int $slotId = 1, string $position): InventoryManagement {
        $slot = $this->fetchSlot($slotId);

        $slot->update([
            'equipped' => true,
            'position' => 'position',
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
    public function giveItem(Item $item): InventoryManagement {
        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id'      => $item->id,
        ]);

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

    protected function fetchSlot(int $slotId): InventorySlot {
        $foundMatching = $this->character->inventory->slots->filter(function($slot) use($slotId) {
            return $slot->id === $slotId && !$slot->equipped;
         })->first();
 
         if (is_null($foundMatching)) {
             throw new \Exception('Item is not in inventory or is already equipped');
         }
 
         $slot = $this->character->inventory->slots->find($slotId);
 
         if (is_null($slot)) {
             throw new \Exception('Slot is not found, did you give the item to the player?');
         }

         return $slot;
    }
}