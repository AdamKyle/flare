<?php

namespace Tests\Unit\Game\Core\Services;

use App\Flare\Models\InventorySlot;
use App\Game\Core\Services\CharacterInventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Facades\App\Flare\Calculators\SellItemCalculator;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateItemAffix;

class CharacterInventoryServiceTest extends TestCase
{
    use RefreshDatabase,
        CreateItem,
        CreateUser,
        CreateItemAffix;

    private $character;

    private $item;

    public function setUp(): void {
        parent::setUp();

        $this->item = $this->createItem([
            'name' => 'Rusty Dagger',
            'type' => 'weapon',
            'base_damage' => 6,
            'cost' => 10,
        ]);

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($this->item)
            ->equipLeftHand($this->item->name)
            ->getCharacterFactory();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
        $this->item      = null;
        $this->characterInventoryService = null;
    }

    public function testInventoryIsNotEmpty() {
        $value = (new CharacterInventoryService)->setCharacter($this->character->getCharacter())->setInventory('weapon')->inventory();

        $this->assertNotEmpty($value);
    }

    public function testInventoryIsNotEmptyWhenFetchingFromSetsWithPositions() {

        $character = $this->character->inventorySetManagement()->createInventorySets(10)->putItemInSet($this->item, 0, 'left-hand', true)->getCharacter();

        $value = (new CharacterInventoryService)->setCharacter($character)->setPositions(['left-hand', 'right-hand'])->setInventory('weapon')->inventory();

        $this->assertNotEmpty($value);
    }

    public function testInventoryIsNotEmptyWhenFetchingFromSetsWithOutPositions() {

        $character = $this->character->inventorySetManagement()->createInventorySets(10)->putItemInSet($this->item, 0, 'left-hand',true)->getCharacter();

        $value = (new CharacterInventoryService)->setCharacter($character)->setInventory('weapon')->inventory();

        $this->assertNotEmpty($value);
    }

    public function testGetTypeShouldBeBow() {
        $item = $this->createItem([
            'name' => 'Rusty Bow',
            'type' => 'bow',
            'base_damage' => 6,
            'cost' => 10,
        ]);

        $value = (new CharacterInventoryService)->getType($item);

        $this->assertEquals('bow', $value);
    }

    public function testGetTypeShouldBeCraftingType() {
        $item = $this->createItem([
            'name' => 'Rusty Bow',
            'type' => 'spell-damage',
            'crafting_type' => 'spell',
            'base_damage' => 6,
            'cost' => 10,
        ]);

        $value = (new CharacterInventoryService)->getType($item);

        $this->assertEquals('spell', $value);
    }
}
