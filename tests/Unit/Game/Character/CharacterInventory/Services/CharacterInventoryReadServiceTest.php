<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Services;

use App\Game\Character\CharacterInventory\Services\CharacterInventoryReadService;
use App\Game\Core\Items\Values\ItemType;
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

class CharacterInventoryReadServiceTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateGem, CreateInventorySets, CreateItem, CreateItemAffix, CreateItemSkill, CreateItemSkillProgression, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?CharacterInventoryReadService $characterInventoryReadService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        $this->characterInventoryReadService = (new CharacterInventoryServiceFactory)->buildReadService();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;

        $this->characterInventoryReadService = null;

    }

    public function test_get_character_inventory_slot_ids()
    {
        $alchemyItem = $this->createItem(['type' => 'alchemy']);
        $questItem = $this->createItem(['type' => 'quest']);
        $regularItem = $this->createItem(['type' => ItemType::WEAPON->value]);

        $character = $this->character->inventoryManagement()->giveItem($alchemyItem)->giveItem($questItem)->giveItem($regularItem)->getCharacter();

        $this->assertCount(1, $this->characterInventoryReadService->inventorySlotIds($character));
    }

    public function test_fetch_equipped_set_with_name()
    {
        $character = $this->character->inventorySetManagement()->createInventorySets()->putItemInSet($this->createItem(), 0, 'left-hand', true)->getCharacter();

        $character->inventorySets()->first()->update([
            'name' => 'Sample',
        ]);

        $character = $character->refresh();

        $this->assertNotEmpty($this->characterInventoryReadService->equipped($character));
    }

    public function test_fetch_equipped_set_with_no_name()
    {
        $character = $this->character->inventorySetManagement()->createInventorySets()->putItemInSet($this->createItem(), 0, 'left-hand', true)->getCharacter();

        $this->assertNotEmpty($this->characterInventoryReadService->equipped($character));
    }

    public function test_fetch_equipped_returns_null()
    {
        $character = $this->character->inventorySetManagement()->createInventorySets()->getCharacter();

        $this->assertEmpty($this->characterInventoryReadService->equipped($character));
    }

    public function test_fetch_equipped_repairs_legacy_equipped_set_positions_before_returning_items(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id, 'is_equipped' => true]);
        $trinketSlot = $this->createInventorySetSlot([
            'inventory_set_id' => $set->id,
            'item_id' => $this->createItem(['type' => 'trinket'])->id,
            'equipped' => true,
            'position' => 'trinket-one',
        ]);
        $artifactSlot = $this->createInventorySetSlot([
            'inventory_set_id' => $set->id,
            'item_id' => $this->createItem(['type' => 'artifact'])->id,
            'equipped' => false,
            'position' => null,
        ]);

        $result = $this->characterInventoryReadService->equipped($character->refresh());

        $returnedTrinket = collect($result['data'])->firstWhere('slot_id', $trinketSlot->id);
        $returnedArtifact = collect($result['data'])->firstWhere('slot_id', $artifactSlot->id);
        $trinketSlot = $trinketSlot->refresh();
        $artifactSlot = $artifactSlot->refresh();

        $this->assertSame('trinket', $returnedTrinket['type']);
        $this->assertSame('trinket', $returnedTrinket['position']);
        $this->assertSame('artifact', $returnedArtifact['type']);
        $this->assertSame('artifact', $returnedArtifact['position']);
        $this->assertTrue($trinketSlot->equipped);
        $this->assertSame('trinket', $trinketSlot->position);
        $this->assertTrue($artifactSlot->equipped);
        $this->assertSame('artifact', $artifactSlot->position);
    }

    public function test_get_quest_items_filters_by_search_text(): void
    {
        $matchingItem = $this->createItem(['type' => 'quest', 'name' => 'ancient key']);
        $otherItem = $this->createItem(['type' => 'quest', 'name' => 'rusty coin']);

        $character = $this->character->inventoryManagement()
            ->giveItem($matchingItem)
            ->giveItem($otherItem)
            ->getCharacter();

        $result = $this->characterInventoryReadService->questItems($character, 'ancient');

        $this->assertCount(1, $result);
        $this->assertSame($matchingItem->id, $result->first()->id);
    }

    public function test_fetch_character_quest_items_paginates_quest_items(): void
    {
        $item = $this->createItem(['type' => 'quest']);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $result = $this->characterInventoryReadService->fetchQuestItems($character);

        $this->assertCount(1, $result['data']);
        $this->assertSame($item->id, $result['data'][0]['item_id']);
    }

    public function test_get_inventory_collection_filters_by_search_text_matching_item_name(): void
    {
        $matchingItem = $this->createItem(['name' => 'Sunfire Blade']);
        $otherItem = $this->createItem(['name' => 'Moonshadow Bow']);

        $character = $this->character->inventoryManagement()
            ->giveItem($matchingItem)
            ->giveItem($otherItem)
            ->getCharacter();

        $result = $this->characterInventoryReadService->fetchInventory($character, 10, 1, 'sunfire');

        $this->assertCount(1, $result['data']);
        $this->assertSame($matchingItem->id, $result['data'][0]['item_id']);
    }

    public function test_get_inventory_collection_filters_by_search_text_matching_item_prefix(): void
    {
        $prefix = $this->createItemAffix(['name' => 'Blazing', 'type' => 'prefix']);
        $matchingItem = $this->createItem(['name' => 'Sword', 'item_prefix_id' => $prefix->id]);
        $otherItem = $this->createItem(['name' => 'Axe']);

        $character = $this->character->inventoryManagement()
            ->giveItem($matchingItem)
            ->giveItem($otherItem)
            ->getCharacter();

        $result = $this->characterInventoryReadService->fetchInventory($character, 10, 1, 'blazing');

        $this->assertCount(1, $result['data']);
        $this->assertSame($matchingItem->id, $result['data'][0]['item_id']);
    }

    public function test_get_inventory_collection_filters_by_search_text_matching_item_suffix(): void
    {
        $suffix = $this->createItemAffix(['name' => 'of the Fox', 'type' => 'suffix']);
        $matchingItem = $this->createItem(['name' => 'Sword', 'item_suffix_id' => $suffix->id]);
        $otherItem = $this->createItem(['name' => 'Axe']);

        $character = $this->character->inventoryManagement()
            ->giveItem($matchingItem)
            ->giveItem($otherItem)
            ->getCharacter();

        $result = $this->characterInventoryReadService->fetchInventory($character, 10, 1, 'fox');

        $this->assertCount(1, $result['data']);
        $this->assertSame($matchingItem->id, $result['data'][0]['item_id']);
    }
}
