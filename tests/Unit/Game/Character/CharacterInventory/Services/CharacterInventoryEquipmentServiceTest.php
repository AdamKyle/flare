<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Services;

use App\Game\Character\CharacterInventory\Services\CharacterInventoryEquipmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

class CharacterInventoryEquipmentServiceTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateGem, CreateInventorySets, CreateItem, CreateItemAffix, CreateItemSkill, CreateItemSkillProgression, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?CharacterInventoryEquipmentService $characterInventoryEquipmentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        $this->characterInventoryEquipmentService = (new CharacterInventoryServiceFactory)->buildEquipmentService();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;

        $this->characterInventoryEquipmentService = null;

    }

    public function test_cannot_unequip_item_when_inventory_is_full()
    {
        $character = $this->character->equipStartingEquipment()->getCharacter();

        $character->update([
            'inventory_max' => 0,
        ]);

        $character = $character->refresh();

        $result = $this->characterInventoryEquipmentService->unequipItem($character, 4);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Your inventory is full. Cannot unequip items. You have no room in your inventory.', $result['message']);
    }

    public function test_cannot_unequip_item_when_item_does_not_exist()
    {
        $character = $this->character->equipStartingEquipment()->getCharacter();

        $result = $this->characterInventoryEquipmentService->unequipItem($character, 4);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('No item found to be unequipped.', $result['message']);
    }

    public function test_can_unequip_item()
    {
        $character = $this->character->equipStartingEquipment()->getCharacter();

        $slot = $character->inventory->slots()->where('equipped', true)->first();

        $result = $this->characterInventoryEquipmentService->unequipItem($character, $slot->id);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Unequipped item: '.$slot->item->affix_name, $result['message']);

        $this->assertFalse($slot->refresh()->equipped);
    }

    public function test_inventory_is_full_cannot_unequip_items()
    {
        $character = $this->character->equipStartingEquipment()->getCharacter();

        $character->update([
            'inventory_max' => 0,
        ]);

        $character = $character->refresh();

        $result = $this->characterInventoryEquipmentService->unequipAll($character);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Your inventory is full. Cannot unequip items. You have no room in your inventory.', $result['message']);
    }

    public function test_can_unequip_all_items()
    {
        $character = $this->character->equipStartingEquipment()->getCharacter();

        $character = $character->refresh();

        $result = $this->characterInventoryEquipmentService->unequipAll($character);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('All items have been unequipped.', $result['message']);

        $character = $character->refresh();

        $this->assertEmpty($character->inventory->slots()->where('equipped', true)->get());
    }
}
