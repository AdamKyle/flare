<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Services;

use App\Game\Character\CharacterInventory\Services\CharacterInventoryComparisonService;
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

class CharacterInventoryComparisonServiceTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateGem, CreateInventorySets, CreateItem, CreateItemAffix, CreateItemSkill, CreateItemSkillProgression, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?CharacterInventoryComparisonService $characterInventoryComparisonService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        $this->characterInventoryComparisonService = (new CharacterInventoryServiceFactory)->buildComparisonService();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;

        $this->characterInventoryComparisonService = null;

    }

    public function test_set_inventory_returns_regular_inventory_slots_matching_the_configured_positions(): void
    {
        $item = $this->createItem();

        $character = $this->character->inventoryManagement()->giveItem($item, false, 'left-hand')->getCharacter();

        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

        $inventory = $this->characterInventoryComparisonService->comparisonInventory($character, null, ['left-hand']);

        $this->assertCount(1, $inventory);
        $this->assertTrue($inventory->contains('id', $slot->id));
    }

    public function test_set_inventory_falls_back_to_equipped_set_slots_matching_the_configured_positions(): void
    {
        $item = $this->createItem();

        $character = $this->character
            ->inventorySetManagement()
            ->createInventorySets()
            ->putItemInSet($item, 0, 'left-hand', true)
            ->getCharacter();

        $inventory = $this->characterInventoryComparisonService->comparisonInventory($character, null, ['left-hand']);

        $this->assertCount(1, $inventory);
        $this->assertSame($item->id, $inventory->first()->item_id);
    }

    public function test_get_type_normalizes_armour_positions_to_armour(): void
    {
        $item = $this->createItem(['type' => 'body']);

        $character = $this->character->getCharacter();

        $type = $this->characterInventoryComparisonService->itemType($item);

        $this->assertSame('armour', $type);
    }

    public function test_get_type_normalizes_spell_damage_to_spell(): void
    {
        $item = $this->createItem(['type' => 'spell-damage']);

        $character = $this->character->getCharacter();

        $type = $this->characterInventoryComparisonService->itemType($item);

        $this->assertSame('spell', $type);
    }

    public function test_get_type_returns_accepted_type_unchanged(): void
    {
        $item = $this->createItem(['type' => 'ring']);

        $character = $this->character->getCharacter();

        $type = $this->characterInventoryComparisonService->itemType($item);

        $this->assertSame('ring', $type);
    }

    public function test_get_type_returns_null_for_unknown_item_type(): void
    {
        $item = $this->createItem(['type' => 'not-a-real-type']);

        $character = $this->character->getCharacter();

        $type = $this->characterInventoryComparisonService->itemType($item);

        $this->assertNull($type);
    }
}
