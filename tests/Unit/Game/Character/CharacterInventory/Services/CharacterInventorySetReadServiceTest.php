<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Services;

use App\Flare\Models\InventorySet;
use App\Game\Character\CharacterInventory\Services\CharacterInventorySetReadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Character\CharacterInventoryServiceFactory;
use Tests\TestCase;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class CharacterInventorySetReadServiceTest extends TestCase
{
    use CreateInventorySets, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?CharacterInventorySetReadService $characterInventorySetReadService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->characterInventorySetReadService = (new CharacterInventoryServiceFactory)->buildSetReadService();
    }

    public function test_get_item_from_inventory_set()
    {
        $item = $this->createItem();
        $character = $this->character->inventorySetManagement()->createInventorySets()->putItemInSet($item, 0)->getCharacter();

        $set = $character->inventorySets()->first();

        $result = $this->characterInventorySetReadService->setItems($character, filters: ['set_id' => $set->id]);

        $this->assertSame($item->id, $result['data'][0]['item_id']);
    }

    public function test_include_named_sets()
    {
        $item = $this->createItem();
        $character = $this->character
            ->inventorySetManagement()
            ->createInventorySets()
            ->putItemInSet($item, 0)
            ->getCharacter();
        $character->inventorySets()->first()->update(['name' => 'Sample']);
        $character = $character->refresh();

        $sets = $this->characterInventorySetReadService->inventorySets($character);

        $this->assertContains('Sample', array_column($sets['data'], 'name'));
    }

    public function test_batch_crafting_set_appears_last_in_set_payload(): void
    {
        $character = $this->character->inventorySetManagement()->createInventorySets(1, true)->getCharacter();
        $this->createInventorySet([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
        ]);

        $sets = $this->characterInventorySetReadService->inventorySets($character->refresh());
        $lastSet = $sets['data'][array_key_last($sets['data'])];

        $this->assertSame(InventorySet::BATCH_CRAFTING_SET_NAME, $lastSet['name']);
        $this->assertTrue($lastSet['is_batch_crafting_set']);
    }

    public function test_batch_crafting_set_is_excluded_from_usable_sets(): void
    {
        $character = $this->character->inventorySetManagement()->createInventorySets(1, true)->getCharacter();
        $this->createInventorySet([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
        ]);

        $sets = $this->characterInventorySetReadService->usableSets($character->refresh());

        $this->assertCount(1, $sets);
        $this->assertNotSame(InventorySet::BATCH_CRAFTING_SET_NAME, $sets[0]['name']);
    }

    public function test_get_set_items_filters_by_provided_set_id(): void
    {
        $matchingItem = $this->createItem();
        $otherItem = $this->createItem();
        $character = $this->character
            ->inventorySetManagement()
            ->createInventorySets(2)
            ->putItemInSet($matchingItem, 0)
            ->putItemInSet($otherItem, 1)
            ->getCharacter();
        $matchingSetId = $character->inventorySets()->orderBy('id')->first()->id;

        $result = $this->characterInventorySetReadService->setItems($character, 10, 1, '', ['set_id' => $matchingSetId]);

        $this->assertCount(1, $result['data']);
        $this->assertSame($matchingItem->id, $result['data'][0]['item_id']);
    }

    public function test_get_set_items_defaults_to_the_equipped_set_when_no_set_id_filter_given(): void
    {
        $item = $this->createItem();
        $character = $this->character
            ->inventorySetManagement()
            ->createInventorySets()
            ->putItemInSet($item, 0, 'left-hand', true)
            ->getCharacter();

        $result = $this->characterInventorySetReadService->setItems($character);

        $this->assertCount(1, $result['data']);
        $this->assertSame($item->id, $result['data'][0]['item_id']);
    }

    public function test_get_set_items_filters_by_search_text_matching_item_name(): void
    {
        $matchingItem = $this->createItem(['name' => 'Sunfire Blade']);
        $otherItem = $this->createItem(['name' => 'Moonshadow Bow']);
        $character = $this->character
            ->inventorySetManagement()
            ->createInventorySets()
            ->putItemInSet($matchingItem, 0, null, true)
            ->putItemInSet($otherItem, 0)
            ->getCharacter();

        $result = $this->characterInventorySetReadService->setItems($character, 10, 1, 'sunfire');

        $this->assertCount(1, $result['data']);
        $this->assertSame($matchingItem->id, $result['data'][0]['item_id']);
    }

    public function test_get_set_items_filters_by_search_text_matching_item_prefix(): void
    {
        $prefix = $this->createItemAffix(['name' => 'Blazing', 'type' => 'prefix']);
        $matchingItem = $this->createItem(['name' => 'Sword', 'item_prefix_id' => $prefix->id]);
        $otherItem = $this->createItem(['name' => 'Axe']);
        $character = $this->character
            ->inventorySetManagement()
            ->createInventorySets()
            ->putItemInSet($matchingItem, 0, null, true)
            ->putItemInSet($otherItem, 0)
            ->getCharacter();

        $result = $this->characterInventorySetReadService->setItems($character, 10, 1, 'blazing');

        $this->assertCount(1, $result['data']);
        $this->assertSame($matchingItem->id, $result['data'][0]['item_id']);
    }

    public function test_get_set_items_filters_by_search_text_matching_item_suffix(): void
    {
        $suffix = $this->createItemAffix(['name' => 'of the Fox', 'type' => 'suffix']);
        $matchingItem = $this->createItem(['name' => 'Sword', 'item_suffix_id' => $suffix->id]);
        $otherItem = $this->createItem(['name' => 'Axe']);
        $character = $this->character
            ->inventorySetManagement()
            ->createInventorySets()
            ->putItemInSet($matchingItem, 0, null, true)
            ->putItemInSet($otherItem, 0)
            ->getCharacter();

        $result = $this->characterInventorySetReadService->setItems($character, 10, 1, 'fox');

        $this->assertCount(1, $result['data']);
        $this->assertSame($matchingItem->id, $result['data'][0]['item_id']);
    }

    public function test_get_paginated_inventory_set_options_returns_lean_fields(): void
    {
        $character = $this->character->inventorySetManagement()->createInventorySets(1, true)->getCharacter();

        $result = $this->characterInventorySetReadService->paginatedDestinationOptions($character);
        $option = $result['data'][0];

        $this->assertArrayHasKey('set_id', $option);
        $this->assertArrayHasKey('name', $option);
        $this->assertArrayHasKey('equipped', $option);
        $this->assertArrayHasKey('is_batch_crafting_set', $option);
        $this->assertArrayHasKey('max_slots', $option);
        $this->assertArrayHasKey('current_slots', $option);
        $this->assertArrayHasKey('remaining_slots', $option);
        $this->assertArrayHasKey('set_number', $option);
        $this->assertArrayHasKey('display_name', $option);
        $this->assertArrayNotHasKey('items', $option);
    }

    public function test_get_paginated_inventory_set_options_searches_by_name(): void
    {
        $character = $this->character->inventorySetManagement()->createInventorySets(1, true)->getCharacter();
        $this->createInventorySet(['character_id' => $character->id, 'name' => 'Findable Set Name', 'is_equipped' => false]);

        $result = $this->characterInventorySetReadService->paginatedDestinationOptions($character->refresh(), 10, 1, 'Findable');

        $this->assertCount(1, $result['data']);
        $this->assertSame('Findable Set Name', $result['data'][0]['name']);
    }

    public function test_get_paginated_inventory_set_options_excludes_the_batch_crafting_set(): void
    {
        $character = $this->character->getCharacter();
        $this->createInventorySet([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
        ]);

        $result = $this->characterInventorySetReadService->paginatedDestinationOptions($character->refresh());
        $batchCraftingSetIncluded = collect($result['data'])->contains(fn ($set) => $set['is_batch_crafting_set']);

        $this->assertFalse($batchCraftingSetIncluded);
    }

    public function test_get_paginated_inventory_set_options_only_returns_empty_unequipped_normal_sets(): void
    {
        $character = $this->character->getCharacter();
        $emptySet = $this->createInventorySet(['character_id' => $character->id, 'is_equipped' => false]);
        $nonEmptySet = $this->createInventorySet(['character_id' => $character->id, 'is_equipped' => false]);
        $this->createInventorySetSlot(['inventory_set_id' => $nonEmptySet->id, 'item_id' => $this->createItem()->id]);
        $equippedSet = $this->createInventorySet(['character_id' => $character->id, 'is_equipped' => true]);
        $this->createInventorySet([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
        ]);

        $result = $this->characterInventorySetReadService->paginatedDestinationOptions($character->refresh());
        $returnedSetIds = array_column($result['data'], 'set_id');

        $this->assertSame([$emptySet->id], $returnedSetIds);
        $this->assertNotContains($nonEmptySet->id, $returnedSetIds);
        $this->assertNotContains($equippedSet->id, $returnedSetIds);
    }

    public function test_get_paginated_inventory_set_options_paginates_after_eligibility_filtering(): void
    {
        $character = $this->character->getCharacter();
        for ($i = 0; $i < 3; $i++) {
            $nonEmptySet = $this->createInventorySet(['character_id' => $character->id, 'is_equipped' => false]);
            $this->createInventorySetSlot(['inventory_set_id' => $nonEmptySet->id, 'item_id' => $this->createItem()->id]);
        }
        $firstEligibleSet = $this->createInventorySet(['character_id' => $character->id, 'is_equipped' => false]);
        $secondEligibleSet = $this->createInventorySet(['character_id' => $character->id, 'is_equipped' => false]);

        $result = $this->characterInventorySetReadService->paginatedDestinationOptions($character->refresh(), 1, 1);

        $this->assertCount(1, $result['data']);
        $this->assertSame($firstEligibleSet->id, $result['data'][0]['set_id']);
        $this->assertTrue($result['meta']['can_load_more']);
        $this->assertSame(2, $result['meta']['pagination']['total']);
        $secondPage = $this->characterInventorySetReadService->paginatedDestinationOptions($character->refresh(), 1, 2);

        $this->assertSame($secondEligibleSet->id, $secondPage['data'][0]['set_id']);
    }

    public function test_get_paginated_inventory_set_options_preserves_a_named_set_name(): void
    {
        $character = $this->character->getCharacter();
        $namedSet = $this->createInventorySet(['character_id' => $character->id, 'name' => 'Boss Gear', 'is_equipped' => false]);

        $result = $this->characterInventorySetReadService->paginatedDestinationOptions($character->refresh());
        $option = collect($result['data'])->firstWhere('set_id', $namedSet->id);

        $this->assertSame('Boss Gear', $option['name']);
        $this->assertSame('Boss Gear', $option['display_name']);
    }

    public function test_get_paginated_inventory_set_options_unnamed_seventh_set_displays_set_seven(): void
    {
        $character = $this->character->inventorySetManagement()->createInventorySets(6)->getCharacter();
        $seventhSet = $this->createInventorySet(['character_id' => $character->id, 'name' => null, 'is_equipped' => false]);

        $result = $this->characterInventorySetReadService->paginatedDestinationOptions($character->refresh());
        $option = collect($result['data'])->firstWhere('set_id', $seventhSet->id);

        $this->assertSame(7, $option['set_number']);
        $this->assertSame('Set 7', $option['display_name']);
    }

    public function test_get_paginated_inventory_set_options_ordinal_includes_ineligible_earlier_normal_sets(): void
    {
        $character = $this->character->inventorySetManagement()->createInventorySets(5)->getCharacter();
        $nonEmptySixthSet = $this->createInventorySet(['character_id' => $character->id, 'is_equipped' => false]);
        $this->createInventorySetSlot(['inventory_set_id' => $nonEmptySixthSet->id, 'item_id' => $this->createItem()->id]);
        $seventhSet = $this->createInventorySet(['character_id' => $character->id, 'name' => null, 'is_equipped' => false]);

        $result = $this->characterInventorySetReadService->paginatedDestinationOptions($character->refresh());
        $option = collect($result['data'])->firstWhere('set_id', $seventhSet->id);

        $this->assertSame(7, $option['set_number']);
        $this->assertSame('Set 7', $option['display_name']);
    }

    public function test_get_paginated_inventory_set_options_batch_crafting_set_does_not_consume_a_normal_ordinal(): void
    {
        $character = $this->character->inventorySetManagement()->createInventorySets(6)->getCharacter();
        $this->createInventorySet([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
        ]);
        $seventhNormalSet = $this->createInventorySet(['character_id' => $character->id, 'name' => null, 'is_equipped' => false]);

        $result = $this->characterInventorySetReadService->paginatedDestinationOptions($character->refresh());
        $option = collect($result['data'])->firstWhere('set_id', $seventhNormalSet->id);

        $this->assertSame(7, $option['set_number']);
        $this->assertSame('Set 7', $option['display_name']);
    }

    public function test_resolve_valid_target_inventory_set_returns_null_for_an_equipped_set(): void
    {
        $set = $this->createInventorySet(['character_id' => $this->character->getCharacter()->id, 'is_equipped' => true]);

        $result = $this->characterInventorySetReadService->resolveValidTarget($this->character->getCharacter(), $set->id);

        $this->assertNull($result);
    }

    public function test_resolve_valid_target_inventory_set_returns_null_for_the_batch_crafting_set(): void
    {
        $set = $this->createInventorySet(['character_id' => $this->character->getCharacter()->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE, 'is_equipped' => false]);

        $result = $this->characterInventorySetReadService->resolveValidTarget($this->character->getCharacter(), $set->id);

        $this->assertNull($result);
    }

    public function test_resolve_valid_target_inventory_set_returns_the_set_when_it_is_a_valid_target(): void
    {
        $set = $this->createInventorySet(['character_id' => $this->character->getCharacter()->id, 'is_equipped' => false]);

        $result = $this->characterInventorySetReadService->resolveValidTarget($this->character->getCharacter(), $set->id);

        $this->assertSame($set->id, $result?->id);
    }

    public function test_resolve_empty_batch_crafting_destination_set_resolves_an_empty_normal_unequipped_set(): void
    {
        $set = $this->createInventorySet(['character_id' => $this->character->getCharacter()->id, 'is_equipped' => false]);

        $result = $this->characterInventorySetReadService->resolveEmptyBatchCraftingDestination($this->character->getCharacter(), $set->id);

        $this->assertSame($set->id, $result?->id);
    }

    public function test_resolve_empty_batch_crafting_destination_set_returns_null_for_a_non_empty_set(): void
    {
        $item = $this->createItem(['name' => 'Occupying Item']);
        $set = $this->createInventorySet(['character_id' => $this->character->getCharacter()->id, 'is_equipped' => false]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);

        $result = $this->characterInventorySetReadService->resolveEmptyBatchCraftingDestination($this->character->getCharacter(), $set->id);

        $this->assertNull($result);
    }

    public function test_resolve_empty_batch_crafting_destination_set_returns_null_for_an_equipped_set(): void
    {
        $set = $this->createInventorySet(['character_id' => $this->character->getCharacter()->id, 'is_equipped' => true]);

        $result = $this->characterInventorySetReadService->resolveEmptyBatchCraftingDestination($this->character->getCharacter(), $set->id);

        $this->assertNull($result);
    }

    public function test_resolve_empty_batch_crafting_destination_set_returns_null_for_the_crafted_items_set(): void
    {
        $set = $this->createInventorySet(['character_id' => $this->character->getCharacter()->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE, 'is_equipped' => false]);

        $result = $this->characterInventorySetReadService->resolveEmptyBatchCraftingDestination($this->character->getCharacter(), $set->id);

        $this->assertNull($result);
    }

    public function test_resolve_empty_batch_crafting_destination_set_returns_null_for_another_characters_set(): void
    {
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $set = $this->createInventorySet(['character_id' => $otherCharacter->id, 'is_equipped' => false]);

        $result = $this->characterInventorySetReadService->resolveEmptyBatchCraftingDestination($this->character->getCharacter(), $set->id);

        $this->assertNull($result);
    }
}
