<?php

namespace Tests\Setup\Character;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;

class InventoryManagement
{
    private $character;

    private $characterFactory;

    private $buildAttackData;

    private $slotIds = [];

    /**
     * Constructor
     */
    public function __construct(Character $character, ?CharacterFactory $characterFactory = null)
    {
        $this->character = $character;
        $this->characterFactory = $characterFactory;

        $this->buildAttackData = resolve(BuildCharacterAttackTypes::class);
    }

    /**
     * Equip the left hand with an item that isn't already equipped.
     *
     * @throws \Exception
     */
    public function equipLeftHand(string $itemName): InventoryManagement
    {
        $slot = $this->fetchSlot($itemName);

        $slot->update([
            'equipped' => true,
            'position' => 'left-hand',
        ]);

        $this->character = $this->character->refresh();

        $this->buildAttackData->buildCache($this->character);

        return $this;
    }

    /**
     * Equip the right hand.
     *
     * @throws \Exception
     */
    public function equipRightHand(string $itemName): InventoryManagement
    {
        $slot = $this->fetchSlot($itemName);

        $slot->update([
            'equipped' => true,
            'position' => 'right-hand',
        ]);

        $this->character = $this->character->refresh();

        $this->buildAttackData->buildCache($this->character);

        return $this;
    }

    /**
     * Equip the Spell slot
     *
     * Accepted: spell-one, spell-two
     *
     * @param  string  $position  | spell-one, spell-two
     *
     * @throws \Exception
     */
    public function equipSpellSlot(string $itemName, string $position = 'spell-one'): InventoryManagement
    {
        $slot = $this->fetchSlot($itemName);

        $slot->update([
            'equipped' => true,
            'position' => $position,
        ]);

        $this->character = $this->character->refresh();

        $this->buildAttackData->buildCache($this->character);

        return $this;
    }

    /**
     * Equip an artifact.
     *
     * @param  string  $position  | artifact-one, artifact-two
     *
     * @throws \Exception
     */
    public function equipArtifact(string $itemName, string $position = 'artifact-one'): InventoryManagement
    {
        $slot = $this->fetchSlot($itemName);

        $slot->update([
            'equipped' => true,
            'position' => $position,
        ]);

        $this->character = $this->character->refresh();

        $this->buildAttackData->buildCache($this->character);

        return $this;
    }

    /**
     * Equip an item.
     *
     * @throws \Exception
     */
    public function equipItem(string $position, string $itemName): InventoryManagement
    {
        $slot = $this->fetchSlot($itemName);

        $slot->update([
            'equipped' => true,
            'position' => $position,
        ]);

        $this->character = $this->character->refresh();

        $this->buildAttackData->buildCache($this->character);

        return $this;
    }

    /**
     * Give item to the character.
     *
     * Ignores the inventory max limit.
     */
    public function giveItem(Item $item, bool $equip = false, ?string $position = null): InventoryManagement
    {
        $this->slotIds[] = $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $item->id,
            'equipped' => $equip,
            'position' => $position,
        ])->id;

        $this->character = $this->character->refresh();

        return $this;
    }

    /**
     * Give the same item multiple times.
     *
     * @return $this
     */
    public function giveItemMultipleTimes(Item $item, int $amount = 1, bool $equip = false, ?string $position = null): InventoryManagement
    {
        for ($i = 1; $i <= $amount; $i++) {
            $this->slotIds[] = $this->character->inventory->slots()->create([
                'inventory_id' => $this->character->inventory->id,
                'item_id' => $item->id,
                'equipped' => $equip,
                'position' => $position,
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
    public function getSlotId(int $index): ?int
    {
        if (isset($this->slotIds[$index])) {
            return $this->slotIds[$index];
        }

        return null;
    }

    /**
     * Return slots ids
     *
     * @return array
     */
    public function getSlotIds(): array
    {
        return $this->slotIds;
    }

    /**
     * Unequip all items.
     */
    public function unequipAll(): InventoryManagement
    {
        $this->character->inventory->slots->each(function ($slot) {
            $slot->update([
                'position' => null,
                'equipped' => false,
            ]);
        });

        $this->character = $this->character->refresh();

        $this->buildAttackData->buildCache($this->character);

        return $this;
    }

    /**
     * Get the character factory.
     */
    public function getCharacterFactory(): CharacterFactory
    {
        return $this->characterFactory;
    }

    /**
     * Get the character back.
     */
    public function getCharacter(): Character
    {
        return $this->character->refresh();
    }

    protected function fetchSlot(string $itemName): InventorySlot
    {
        $foundMatching = $this->character->inventory->slots->filter(function ($slot) use ($itemName) {
            return $slot->item->name === $itemName;
        })->first();

        if (is_null($foundMatching)) {
            throw new \Exception('Item is not in inventory or is already equipped');
        }

        return $foundMatching;
    }
}
