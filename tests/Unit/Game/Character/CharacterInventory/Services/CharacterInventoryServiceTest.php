<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Services;

use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;
use App\Game\Skills\Services\DisenchantService;
use App\Game\Skills\Services\UpdateCharacterSkillsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Character\CharacterInventoryServiceFactory;
use Tests\TestCase;
use Tests\Traits\CreateAlchemyBagSlot;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateItemSkill;
use Tests\Traits\CreateItemSkillProgression;

class CharacterInventoryServiceTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateGem, CreateInventorySets, CreateItem, CreateItemAffix, CreateItemSkill, CreateItemSkillProgression, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?CharacterInventoryService $characterInventoryService;

    private ?MockInterface $updateCharacterSkillsService = null;

    private ?MockInterface $disenchantService = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        $this->updateCharacterSkillsService = Mockery::mock(UpdateCharacterSkillsService::class);
        $this->disenchantService = Mockery::mock(DisenchantService::class);
        $this->characterInventoryService = (new CharacterInventoryServiceFactory)->buildFacade(
            $this->updateCharacterSkillsService,
            $this->disenchantService,
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();

        $this->character = null;

        $this->characterInventoryService = null;

        $this->updateCharacterSkillsService = null;

        $this->disenchantService = null;
    }

    public function test_get_inventory_for_api()
    {
        $character = $this->character->inventorySetManagement()
            ->createInventorySets()
            ->getCharacterFactory()
            ->equipStartingEquipment()
            ->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->getInventoryForApi();

        $this->assertNotEmpty($result['equipped']);
        $this->assertNotEmpty($result['savable_sets']);
    }

    public function test_get_inventory_for_type_savable_sets()
    {
        $character = $this->character->inventorySetManagement()
            ->createInventorySets()
            ->getCharacterFactory()
            ->equipStartingEquipment()
            ->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->getInventoryForType('savable_sets');

        $this->assertNotEmpty($result);
    }

    public function test_get_inventory_for_type_equipped()
    {
        $character = $this->character->inventorySetManagement()
            ->createInventorySets()
            ->getCharacterFactory()
            ->equipStartingEquipment()
            ->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->getInventoryForType('equipped');

        $this->assertNotEmpty($result);
    }

    public function test_get_inventory_for_type_sets()
    {
        $character = $this->character->inventorySetManagement()
            ->createInventorySets(2)
            ->putItemInSet($this->createItem(), 1, 'left-hand', true)
            ->getCharacterFactory()
            ->equipStartingEquipment()
            ->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->getInventoryForType('sets');

        $this->assertNotEmpty($result['sets']);
        $this->assertTrue($result['set_equipped']);
    }

    public function test_get_inventory_for_quest_items()
    {
        $character = $this->character->inventoryManagement()
            ->giveItem($this->createItem([
                'type' => 'quest',
            ]))
            ->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->getInventoryForType('quest_items');

        $this->assertNotEmpty($result);
    }

    public function test_get_inventory_for_usable_items()
    {
        $item = $this->createItem([
            'type' => 'alchemy',
            'usable' => true,
        ]);

        $character = $this->character->getCharacter();

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);

        $result = $this->characterInventoryService->setCharacter($character)->getInventoryForType('usable_items');

        $this->assertNotEmpty($result);
    }

    public function test_get_inventory_data_when_no_valid_type_passed_in()
    {
        $character = $this->character->inventorySetManagement()
            ->createInventorySets()
            ->getCharacterFactory()
            ->equipStartingEquipment()
            ->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->getInventoryForType('something');

        $this->assertNotEmpty($result['equipped']);
        $this->assertNotEmpty($result['savable_sets']);
    }

    public function test_get_no_name_for_no_equipped_set()
    {
        $character = $this->character->getCharacter();

        $name = $this->characterInventoryService->setCharacter($character)->getEquippedInventorySetName();

        $this->assertNull($name);
    }

    public function test_get_name_for_name_equipped_set()
    {
        $item = $this->createItem();

        $character = $this->character->inventorySetManagement()->createInventorySets()->putItemInSet($item, 0, 'left-hand', true)->getCharacter();

        $character->inventorySets()->first()->update([
            'name' => 'Sample',
        ]);

        $character = $character->refresh();

        $name = $this->characterInventoryService->setCharacter($character)->getEquippedInventorySetName();

        $this->assertEquals('Sample', $name);
    }

    public function test_get_name_for_non_named_set_equipped()
    {
        $item = $this->createItem();

        $character = $this->character->inventorySetManagement()->createInventorySets()->putItemInSet($item, 0, 'left-hand', true)->getCharacter();

        $name = $this->characterInventoryService->setCharacter($character)->getEquippedInventorySetName();

        $this->assertEquals('Set 1', $name);
    }
}
