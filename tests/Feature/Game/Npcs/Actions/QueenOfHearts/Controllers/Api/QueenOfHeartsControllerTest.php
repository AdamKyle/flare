<?php

namespace Tests\Feature\Game\Npcs\Actions\QueenOfHearts\Controllers\Api;

use App\Flare\Models\ItemSkill;
use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Core\Items\Values\RandomAffixTier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class QueenOfHeartsControllerTest extends TestCase
{
    use CreateGameMap, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CharacterFactory $character = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_initial_inventory_response_includes_slots_and_cost_lookups()
    {
        $this->character->givePlayerLocation();

        $uniqueItem = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'type' => 'prefix',
                'randomly_generated' => true,
            ])->id,
        ]);

        $nonUniqueItem = $this->createItem([
            'type' => 'weapon',
        ]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($uniqueItem)
            ->giveItem($nonUniqueItem)
            ->getCharacter();

        $slot = $character->inventory->slots()->where('item_id', $uniqueItem->id)->first();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory/uniques');

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(1, $jsonData['unique_slots']);
        $this->assertCount(1, $jsonData['non_unique_slots']);
        $this->assertArrayHasKey('reroll', $jsonData['costs']);
        $this->assertArrayHasKey('movement', $jsonData['costs']);
        $this->assertArrayHasKey($slot->id, $jsonData['costs']['movement']);
    }

    public function test_paginated_unique_items_endpoint_respects_per_page_and_search()
    {
        $this->character->givePlayerLocation();

        $uniqueOne = $this->createItem([
            'type' => 'weapon',
            'name' => 'Alpha Unique',
            'item_prefix_id' => $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => true])->id,
        ]);

        $uniqueTwo = $this->createItem([
            'type' => 'weapon',
            'name' => 'Beta Unique',
            'item_prefix_id' => $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => true])->id,
        ]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($uniqueOne)
            ->giveItem($uniqueTwo)
            ->getCharacter();

        $firstPage = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/queen-of-hearts/unique-items', [
                'per_page' => 1,
                'page' => 1,
            ]);

        $firstPageData = json_decode($firstPage->getContent(), true);

        $searchResponse = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/queen-of-hearts/unique-items', [
                'per_page' => 15,
                'page' => 1,
                'search_text' => 'Beta',
            ]);

        $searchData = json_decode($searchResponse->getContent(), true);

        $this->assertEquals(200, $firstPage->status());
        $this->assertCount(1, $firstPageData['data']);
        $this->assertTrue($firstPageData['meta']['can_load_more']);
        $this->assertCount(1, $searchData['data']);
        $this->assertEquals($uniqueTwo->id, $searchData['data'][0]['item_id']);
    }

    public function test_paginated_destination_items_endpoint_includes_unique_and_non_unique_but_excludes_source_and_foreign_slots()
    {
        $this->character->givePlayerLocation();

        $sourceItem = $this->createItem([
            'type' => 'weapon',
            'name' => 'Source Unique',
            'item_prefix_id' => $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => true])->id,
        ]);

        $uniqueDestination = $this->createItem([
            'type' => 'weapon',
            'name' => 'Unique Destination',
            'item_prefix_id' => $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => true])->id,
        ]);

        $nonUniqueDestination = $this->createItem([
            'type' => 'weapon',
            'name' => 'Common Destination',
        ]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($sourceItem)
            ->giveItem($uniqueDestination)
            ->giveItem($nonUniqueDestination)
            ->getCharacter();

        $sourceSlot = $character->inventory->slots()->where('item_id', $sourceItem->id)->first();
        $uniqueDestinationSlot = $character->inventory->slots()->where('item_id', $uniqueDestination->id)->first();
        $nonUniqueDestinationSlot = $character->inventory->slots()->where('item_id', $nonUniqueDestination->id)->first();

        $foreignItem = $this->createItem(['type' => 'weapon', 'name' => 'Foreign Item']);
        (new CharacterFactory)->createBaseCharacter()
            ->inventoryManagement()
            ->giveItem($foreignItem)
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/queen-of-hearts/destination-items', [
                'source_slot_id' => $sourceSlot->id,
                'per_page' => 15,
                'page' => 1,
            ]);

        $jsonData = json_decode($response->getContent(), true);
        $returnedSlotIds = array_column($jsonData['data'], 'slot_id');
        $returnedItemIds = array_column($jsonData['data'], 'item_id');

        $this->assertEquals(200, $response->status());
        $this->assertCount(2, $jsonData['data']);
        $this->assertContains($uniqueDestinationSlot->id, $returnedSlotIds);
        $this->assertContains($nonUniqueDestinationSlot->id, $returnedSlotIds);
        $this->assertNotContains($sourceSlot->id, $returnedSlotIds);
        $this->assertNotContains($foreignItem->id, $returnedItemIds);
    }

    public function test_paginated_destination_items_endpoint_respects_per_page_and_search()
    {
        $this->character->givePlayerLocation();

        $sourceItem = $this->createItem(['type' => 'weapon', 'name' => 'Source Item']);
        $itemOne = $this->createItem(['type' => 'weapon', 'name' => 'Alpha Common']);
        $itemTwo = $this->createItem(['type' => 'weapon', 'name' => 'Beta Common']);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($sourceItem)
            ->giveItem($itemOne)
            ->giveItem($itemTwo)
            ->getCharacter();

        $sourceSlot = $character->inventory->slots()->where('item_id', $sourceItem->id)->first();

        $firstPage = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/queen-of-hearts/destination-items', [
                'source_slot_id' => $sourceSlot->id,
                'per_page' => 1,
                'page' => 1,
            ]);

        $firstPageData = json_decode($firstPage->getContent(), true);

        $searchResponse = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/queen-of-hearts/destination-items', [
                'source_slot_id' => $sourceSlot->id,
                'per_page' => 15,
                'page' => 1,
                'search_text' => 'Beta',
            ]);

        $searchData = json_decode($searchResponse->getContent(), true);

        $this->assertEquals(200, $firstPage->status());
        $this->assertCount(1, $firstPageData['data']);
        $this->assertTrue($firstPageData['meta']['can_load_more']);
        $this->assertCount(1, $searchData['data']);
        $this->assertEquals($itemTwo->id, $searchData['data'][0]['item_id']);
    }

    public function test_paginated_destination_items_endpoint_result_changes_when_source_slot_changes()
    {
        $this->character->givePlayerLocation();

        $itemA = $this->createItem(['type' => 'weapon', 'name' => 'Item A']);
        $itemB = $this->createItem(['type' => 'weapon', 'name' => 'Item B']);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($itemA)
            ->giveItem($itemB)
            ->getCharacter();

        $slotA = $character->inventory->slots()->where('item_id', $itemA->id)->first();
        $slotB = $character->inventory->slots()->where('item_id', $itemB->id)->first();

        $responseWithSourceA = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/queen-of-hearts/destination-items', [
                'source_slot_id' => $slotA->id,
                'per_page' => 15,
                'page' => 1,
            ]);

        $dataWithSourceA = json_decode($responseWithSourceA->getContent(), true);

        $responseWithSourceB = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/queen-of-hearts/destination-items', [
                'source_slot_id' => $slotB->id,
                'per_page' => 15,
                'page' => 1,
            ]);

        $dataWithSourceB = json_decode($responseWithSourceB->getContent(), true);

        $this->assertCount(1, $dataWithSourceA['data']);
        $this->assertEquals($slotB->id, $dataWithSourceA['data'][0]['slot_id']);
        $this->assertCount(1, $dataWithSourceB['data']);
        $this->assertEquals($slotA->id, $dataWithSourceB['data'][0]['slot_id']);
    }

    public function test_destination_items_endpoint_rejects_a_nonexistent_or_foreign_source_slot()
    {
        $this->character->givePlayerLocation();
        $character = $this->character->getCharacter();

        $nonexistentResponse = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/queen-of-hearts/destination-items', [
                'source_slot_id' => 999999999,
                'per_page' => 15,
                'page' => 1,
            ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $foreignItem = $this->createItem(['type' => 'weapon']);
        $foreignCharacter = (new CharacterFactory)->createBaseCharacter()
            ->inventoryManagement()
            ->giveItem($foreignItem)
            ->getCharacter();
        $foreignSlot = $foreignCharacter->inventory->slots()->where('item_id', $foreignItem->id)->first();

        $foreignSlotResponse = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/queen-of-hearts/destination-items', [
                'source_slot_id' => $foreignSlot->id,
                'per_page' => 15,
                'page' => 1,
            ]);

        $this->assertEquals(422, $nonexistentResponse->status());
        $this->assertArrayHasKey('source_slot_id', json_decode($nonexistentResponse->getContent(), true)['errors']);
        $this->assertEquals(422, $foreignSlotResponse->status());
        $this->assertArrayHasKey('message', json_decode($foreignSlotResponse->getContent(), true));
    }

    public function test_successful_reroll_response_retains_the_full_cost_lookup()
    {
        $hellMap = $this->createGameMap(['name' => 'Hell', 'path' => 'hell-path']);

        $this->character->givePlayerLocation(16, 16, $hellMap);

        $uniqueItem = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'type' => 'prefix',
                'randomly_generated' => true,
                'cost' => RandomAffixTier::LEGENDARY->value,
            ])->id,
        ]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($uniqueItem)
            ->getCharacter();

        $character->update([
            'gold_dust' => 1000000,
            'shards' => 10000,
        ]);

        $character = $character->refresh();

        $slot = $character->inventory->slots()->where('item_id', $uniqueItem->id)->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/random-enchant/reroll', [
                'selected_slot_id' => $slot->id,
                'selected_affix' => 'prefix',
                'selected_reroll_type' => 'base',
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->status());
        $this->assertArrayHasKey('reroll', $jsonData['costs']);
        $this->assertArrayHasKey('movement', $jsonData['costs']);
        $this->assertNotNull($jsonData['result_preview']);
        $this->assertSame($slot->id, $jsonData['result_preview']['inventory_slot_id']);
    }

    public function test_successful_movement_response_retains_the_full_cost_lookup()
    {
        $hellMap = $this->createGameMap(['name' => 'Hell', 'path' => 'hell-path']);

        $this->character->givePlayerLocation(16, 16, $hellMap);

        $accessItem = $this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectType::QUEEN_OF_HEARTS->value,
        ]);

        $uniqueItemToMove = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'type' => 'prefix',
                'randomly_generated' => true,
                'cost' => RandomAffixTier::LEGENDARY->value,
            ])->id,
        ]);

        $untouchedUniqueItem = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'type' => 'prefix',
                'randomly_generated' => true,
                'cost' => RandomAffixTier::LEGENDARY->value,
            ])->id,
        ]);

        $nonUniqueItem = $this->createItem([
            'type' => 'weapon',
        ]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($accessItem)
            ->giveItem($uniqueItemToMove)
            ->giveItem($untouchedUniqueItem)
            ->giveItem($nonUniqueItem)
            ->getCharacter();

        $character->update([
            'gold_dust' => 1000000,
            'shards' => 10000,
        ]);

        $character = $character->refresh();

        $slot = $character->inventory->slots()->where('item_id', $uniqueItemToMove->id)->first();
        $secondSlot = $character->inventory->slots()->where('item_id', $nonUniqueItem->id)->first();
        $untouchedSlot = $character->inventory->slots()->where('item_id', $untouchedUniqueItem->id)->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/random-enchant/move', [
                'selected_slot_id' => $slot->id,
                'selected_secondary_slot_id' => $secondSlot->id,
                'selected_affix' => 'prefix',
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->status());
        $this->assertArrayHasKey('reroll', $jsonData['costs']);
        $this->assertArrayHasKey('movement', $jsonData['costs']);
        $this->assertArrayHasKey($untouchedSlot->id, $jsonData['costs']['movement']);
        $this->assertNotNull($jsonData['source_result_preview']);
        $this->assertNotNull($jsonData['destination_result_preview']);
    }

    public function test_paginated_unique_items_endpoint_with_populated_rows_does_not_lazy_load()
    {
        $this->character->givePlayerLocation();

        $itemSkill = ItemSkill::create([
            'name' => 'Weapon Mastery',
            'description' => 'Increases weapon proficiency.',
            'max_level' => 10,
            'total_kills_needed' => 100,
        ]);

        $decoratedUnique = $this->createItem([
            'type' => 'weapon',
            'name' => 'Decorated Unique',
            'item_prefix_id' => $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => true])->id,
            'item_suffix_id' => $this->createItemAffix(['type' => 'suffix', 'randomly_generated' => true])->id,
            'holy_stacks' => 5,
        ]);

        $decoratedUnique->appliedHolyStacks()->create([
            'item_id' => $decoratedUnique->id,
            'devouring_darkness_bonus' => 0.1,
            'stat_increase_bonus' => 0.1,
        ]);

        $decoratedUnique->itemSkillProgressions()->create([
            'item_id' => $decoratedUnique->id,
            'item_skill_id' => $itemSkill->id,
            'current_level' => 1,
            'current_kill' => 10,
            'is_training' => false,
        ]);

        $decoratedUnique = $decoratedUnique->refresh();

        $secondUnique = $this->createItem([
            'type' => 'weapon',
            'name' => 'Second Unique',
            'item_prefix_id' => $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => true])->id,
        ]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($decoratedUnique)
            ->giveItem($secondUnique)
            ->getCharacter();

        Model::preventLazyLoading();

        try {
            $response = $this->actingAs($character->user)
                ->call('GET', '/api/character/'.$character->id.'/queen-of-hearts/unique-items', [
                    'per_page' => 15,
                    'page' => 1,
                ]);

            $response->assertOk();

            $data = json_decode($response->getContent(), true);

            $this->assertCount(2, $data['data']);
            $this->assertArrayHasKey('preview', $data['data'][0]);
        } finally {
            Model::preventLazyLoading(false);
        }
    }
}
