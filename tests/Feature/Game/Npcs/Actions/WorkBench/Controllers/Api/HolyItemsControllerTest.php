<?php

namespace Tests\Feature\Game\Npcs\Actions\WorkBench\Controllers\Api;

use App\Game\Core\Currency\Services\CurrencyLimit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAlchemyBagSlot;
use Tests\Traits\CreateHolyStack;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateItemSkill;
use Tests\Traits\CreateItemSkillProgression;

class HolyItemsControllerTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateHolyStack, CreateItem, CreateItemAffix, CreateItemSkill, CreateItemSkillProgression, RefreshDatabase;

    private ?CharacterFactory $character = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_get_smithing_items()
    {
        $oil = $this->createItem([
            'type' => 'alchemy',
            'holy_level' => 5,
            'can_use_on_other_items' => true,
        ]);

        $character = $this->character->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem(
                $this->createItem([
                    'holy_stacks' => 20,
                ])
            )
            ->getCharacter();

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $oil->id,
            'amount' => 1,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory/smiths-workbench');

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(1, $jsonData['items']);
        $this->assertCount(1, $jsonData['alchemy_items']);
        $this->assertEquals(1, $jsonData['alchemy_items'][0]['stack_amount']);
        $this->assertArrayHasKey('preview', $jsonData['items'][0]);
    }

    public function test_get_smithing_items_includes_cost_lookup()
    {
        $oil = $this->createItem([
            'type' => 'alchemy',
            'holy_level' => 3,
            'can_use_on_other_items' => true,
        ]);

        $item = $this->createItem([
            'holy_stacks' => 10,
        ]);

        $character = $this->character->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $alchemySlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $oil->id,
            'amount' => 1,
        ]);

        $itemSlot = $character->inventory->slots()->where('item_id', $item->id)->first();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory/smiths-workbench');

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(3000, $jsonData['costs'][$itemSlot->id][$alchemySlot->id]);
    }

    public function test_paginated_items_endpoint_respects_per_page_and_search()
    {
        $itemOne = $this->createItem(['name' => 'Alpha Sword', 'holy_stacks' => 20]);
        $itemTwo = $this->createItem(['name' => 'Beta Sword', 'holy_stacks' => 20]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($itemOne)
            ->giveItem($itemTwo)
            ->getCharacter();

        $firstPage = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory/smiths-workbench/items', [
                'per_page' => 1,
                'page' => 1,
            ]);

        $firstPageData = json_decode($firstPage->getContent(), true);

        $searchResponse = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory/smiths-workbench/items', [
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

    public function test_paginated_items_endpoint_excludes_items_with_no_remaining_holy_stacks()
    {
        $eligibleItem = $this->createItem(['name' => 'Eligible Sword', 'holy_stacks' => 20]);
        $fullyStackedItem = $this->createItem(['name' => 'Full Sword', 'holy_stacks' => 1]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($eligibleItem)
            ->giveItem($fullyStackedItem)
            ->getCharacter();

        $this->createHolyStack([
            'item_id' => $fullyStackedItem->id,
            'devouring_darkness_bonus' => 0.1,
            'stat_increase_bonus' => 0.1,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory/smiths-workbench/items', [
                'per_page' => 15,
                'page' => 1,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(1, $jsonData['data']);
        $this->assertEquals($eligibleItem->id, $jsonData['data'][0]['item_id']);
    }

    public function test_paginated_items_endpoint_only_returns_requested_characters_items()
    {
        $ownItem = $this->createItem(['name' => 'Own Sword', 'holy_stacks' => 20]);
        $otherItem = $this->createItem(['name' => 'Other Sword', 'holy_stacks' => 20]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($ownItem)
            ->getCharacter();

        (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($otherItem);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory/smiths-workbench/items', [
                'per_page' => 15,
                'page' => 1,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(1, $jsonData['data']);
        $this->assertEquals($ownItem->id, $jsonData['data'][0]['item_id']);
    }

    public function test_paginated_oils_endpoint_respects_per_page_and_search()
    {
        $oilOne = $this->createItem(['type' => 'alchemy', 'name' => 'Alpha Oil', 'holy_level' => 1, 'can_use_on_other_items' => true]);
        $oilTwo = $this->createItem(['type' => 'alchemy', 'name' => 'Beta Oil', 'holy_level' => 1, 'can_use_on_other_items' => true]);

        $character = $this->character->getCharacter();

        $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oilOne->id, 'amount' => 1]);
        $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oilTwo->id, 'amount' => 1]);

        $firstPage = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory/smiths-workbench/oils', [
                'per_page' => 1,
                'page' => 1,
            ]);

        $firstPageData = json_decode($firstPage->getContent(), true);

        $searchResponse = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory/smiths-workbench/oils', [
                'per_page' => 15,
                'page' => 1,
                'search_text' => 'Beta',
            ]);

        $searchData = json_decode($searchResponse->getContent(), true);

        $this->assertEquals(200, $firstPage->status());
        $this->assertCount(1, $firstPageData['data']);
        $this->assertTrue($firstPageData['meta']['can_load_more']);
        $this->assertCount(1, $searchData['data']);
        $this->assertEquals($oilTwo->id, $searchData['data'][0]['item_id']);
    }

    public function test_apply_oil()
    {

        $item = $this->createItem([
            'type' => 'weapon',
            'holy_stacks' => 20,
        ]);

        $item = $item->refresh();

        $oil = $this->createItem([
            'type' => 'alchemy',
            'holy_level' => 1,
            'can_use_on_other_items' => true,
        ]);

        $character = $this->character->inventoryManagement()->giveItem(
            $item
        )->getCharacter();

        $alchemySlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $oil->id,
            'amount' => 1,
        ]);

        $character->update([
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
        ]);

        $character = $character->refresh();

        $slot = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->type === 'weapon';
        })->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/smithy-workbench/apply', [
                '_token' => csrf_token(),
                'item_id' => $slot->item->id,
                'alchemy_slot_id' => $alchemySlot->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $character = $character->refresh();

        $this->assertNotNull($character->inventory->slots->filter(function ($slot) {
            return $slot->item->holy_stacks_applied === 1;
        })->first());

        $this->assertCount(1, $jsonData['items']);
        $this->assertCount(0, $jsonData['alchemy_items']);
        $this->assertEquals(0, $character->alchemyBag->slots()->where('id', $alchemySlot->id)->count());
        $this->assertNotNull($jsonData['result_preview']);
        $this->assertSame(1, $jsonData['result_preview']['holy_stacks_applied']);
    }

    public function test_apply_oil_returns_representative_validation_error_response()
    {
        $item = $this->createItem([
            'type' => 'weapon',
            'holy_stacks' => 20,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $alchemySlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);

        $response = $this->actingAs($character->user)
            ->json('POST', '/api/character/'.$character->id.'/smithy-workbench/apply', [
                'alchemy_slot_id' => $alchemySlot->id,
            ]);

        $response->assertStatus(422);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Error. Invalid Input.', $jsonData['errors']['item_id'][0]);
    }

    public function test_paginated_items_endpoint_with_populated_rows_does_not_lazy_load()
    {
        $prefix = $this->createItemAffix(['type' => 'prefix']);
        $suffix = $this->createItemAffix(['type' => 'suffix']);

        $itemSkill = $this->createItemSkill([
            'name' => 'Weapon Mastery',
            'description' => 'Increases weapon proficiency.',
            'max_level' => 10,
            'total_kills_needed' => 100,
        ]);

        $decoratedItem = $this->createItem([
            'name' => 'Decorated Sword',
            'holy_stacks' => 5,
            'item_prefix_id' => $prefix->id,
            'item_suffix_id' => $suffix->id,
        ]);

        $this->createHolyStack([
            'item_id' => $decoratedItem->id,
            'devouring_darkness_bonus' => 0.1,
            'stat_increase_bonus' => 0.1,
        ]);

        $this->createItemSkillProgression([
            'item_id' => $decoratedItem->id,
            'item_skill_id' => $itemSkill->id,
            'current_level' => 1,
            'current_kill' => 10,
            'is_training' => false,
        ]);

        $decoratedItem = $decoratedItem->refresh();

        $plainItem = $this->createItem(['name' => 'Plain Sword', 'holy_stacks' => 20]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($decoratedItem)
            ->giveItem($plainItem)
            ->getCharacter();

        Model::preventLazyLoading();

        try {
            $response = $this->actingAs($character->user)
                ->call('GET', '/api/character/'.$character->id.'/inventory/smiths-workbench/items', [
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
