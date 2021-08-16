<?php

namespace Tests\Setup\Character;

use App\Flare\Models\Character;
use Tests\Traits\CreateInventorySets;

class InventorySetManagement {

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
    public function __construct(Character $character, CharacterFactory $characterFactory = null) {
        $this->character        = $character;
        $this->characterFactory = $characterFactory;
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
     * Assign x inventory Sets.
     *
     * @param int $amount
     * @return $this
     */
    public function createInventorySets(int $amount = 1): InventorySetManagement {
        for ($i = 1; $i <= $amount; $i++) {
            $inventorySetIds[] = $this->createInventorySet([
                'character_id' => $this->character->id,
            ])->id;
        }

        return $this;
    }

    /**
     * Gets the ID or throws an exception.
     *
     * @param int $index
     * @return int
     * @throws \Exception
     */
    public function getInventorySetId(int $index): int {
        if (isset($this->inventorySetIds[$index])) {
            return $this->inventorySetIds[$index];
        }

        throw new \Exception('Index does not exist for inventory sets on this character.');
    }
}
