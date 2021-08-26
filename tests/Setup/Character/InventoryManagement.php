<?php

namespace Tests\Setup\Character;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;

class InventoryManagement {

    private $character;

    private $characterFactory;

    private $slotIds = [];
    /**
     * Constructor
     *
     * @param Character $character
     * @param CharacterFactory|null $characterFactory
     */
    public function __construct(Character $character, CharacterFactory $characterFactory = null) {
        $this->character        = $character;
        $this->characterFactory = $characterFactory;
    }

    /**
     * Equip the left hand with an item that isn't already equipped.
     *
     * @param string $itemName
     * @return InventoryManagement
     * @throws \Exception
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
     * @param string $itemName
     * @return InventoryManagement
     * @throws \Exception
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
     * @param string $itemName
     * @param string $position | spell-one, spell-two
     * @return InventoryManagement
     * @throws \Exception
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
     * @param string $itemName
     * @param string $position | artifact-one, artifact-two
     * @return InventoryManagement
     * @throws \Exception
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
     * @param string $position
     * @param string $itemName
     * @return InventoryManagement
     * @throws \Exception
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
     * @param bool $equip
     * @param string|null $position
     * @return InventoryManagement
     */
    public function giveItem(Item $item, bool $equip = false, string $position = null): InventoryManagement {
        $this->slotIds[] = $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id'      => $item->id,
            'equipped'     => $equip,
            'position'     => $position,
        ])->id;

        $this->character = $this->character->refresh();

        return $this;
    }

    /**
     * Give the same item multiple times.
     *
     * @param Item $item
     * @param int $amount
     * @return $this
     */
    public function giveItemMultipleTimes(Item $item, int $amount = 1, bool $equip = false, string $position = null): InventoryManagement {
        for ($i = 1; $i <= $amount; $i++) {
            $this->slotIds[] = $this->character->inventory->slots()->create([
                'inventory_id' => $this->character->inventory->id,
                'item_id'      => $item->id,
                'equipped'     => $equip,
                'position'     => $position,
            ])->id;
        }

        $this->character = $this->character->refresh();

        return $this;
    }

    /**
     * Get the slot id of an item given to th character.
     *
     * You must first use the giveItem command, before calling this.
     * The index param is the index of the array starting at 0.
     *
     * @param int $index
     * @return int|null
     */
    public function getSlotId(int $index) {
        if (isset($this->slotIds[$index])) {
            return $this->slotIds[$index];
        }

        return null;
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
