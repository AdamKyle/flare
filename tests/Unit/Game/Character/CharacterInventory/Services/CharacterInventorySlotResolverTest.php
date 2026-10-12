<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Services;

use App\Game\Character\CharacterInventory\Services\CharacterInventorySlotResolver;
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

class CharacterInventorySlotResolverTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateGem, CreateInventorySets, CreateItem, CreateItemAffix, CreateItemSkill, CreateItemSkillProgression, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?CharacterInventorySlotResolver $characterInventorySlotResolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        $this->characterInventorySlotResolver = (new CharacterInventoryServiceFactory)->buildSlotResolver();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;

        $this->characterInventorySlotResolver = null;

    }

    public function test_get_set_slot_for_item_details_resolves_the_exact_set_slot()
    {
        $item = $this->createItem();

        $character = $this->character->inventorySetManagement()->createInventorySets()->putItemInSet($item, 0)->getCharacter();

        $setSlot = $character->inventorySets()->first()->slots()->where('item_id', $item->id)->first();

        $result = $this->characterInventorySlotResolver->setSlot($character, $item, $setSlot->id);

        $this->assertNotNull($result);
        $this->assertSame($setSlot->id, $result->id);
    }
}
