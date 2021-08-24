<?php

namespace Tests\Setup\Character;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use Tests\Traits\CreateInventorySets;

class InventorySetManagement
{

    use CreateInventorySets;

    private Character $character;

    private ?CharacterFactory $characterFactory;

    private $inventorySetIds = [];

    /**
     * Constructor
     *
     * @param Character $character
     * @param CharacterFactory|null $characterFactory
     */
    public function __construct(Character $character, CharacterFactory $characterFactory = null)
    {
        $this->character = $character;
        $this->characterFactory = $characterFactory;
    }

    /**
     * Get the character factory.
     *
     * @return CharacterFactory
     */
    public function getCharacterFactory(): CharacterFactory
    {
        return $this->characterFactory;
    }

    /**
     * Assign x inventory Sets.
     *
     * @param int $amount
     * @return $this
     */
    public function createInventorySets(int $amount = 1): InventorySetManagement
    {
        for ($i = 1; $i <= $amount; $i++) {
            $this->inventorySetIds[] = $this->createInventorySet([
                'character_id' => $this->character->id,
            ])->id;
        }

        return $this;
    }

    /**
     * Puts an item in the characters inventory set.
     *
     * @param Item $item
     * @param int $setIndex
     * @param bool $equipped
     * @return InventorySetManagement
     * @throws \Exception
     */
    public function putItemInSet(Item $item, int $setIndex, string $position = null, bool $equipped = false): InventorySetManagement
    {
        $setId = $this->getInventorySetId($setIndex);

        $this->createInventorySetSlot([
            'inventory_set_id' => $setId,
            'item_id' => $item->id,
            'equipped' => $equipped,
            'position' => $position,
        ]);

        if ($equipped) {
            $this->character->inventorySets()->find($setId)->update(['is_equipped' => true]);

            $this->character->inventory->slots()->where('equipped', true)->update(['equipped' => false]);
        }

        return $this;
    }

    public function updateSet(int $index, array $options): InventorySetManagement {
        $setId = $this->getInventorySetId($index);

        $this->character->inventorySets()->find($setId)->update($options);

        return $this;
    }

    /**
     * Get the character.
     *
     * @return Character
     */
    public function getCharacter(): Character
    {
        return $this->character->refresh();
    }

    /**
     * Gets the ID or throws an exception.
     *
     * @param int $index
     * @return int
     * @throws \Exception
     */
    public function getInventorySetId(int $index): int
    {
        if (isset($this->inventorySetIds[$index])) {
            return $this->inventorySetIds[$index];
        }

        throw new \Exception('Index does not exist for inventory sets on this character.');
    }
}
