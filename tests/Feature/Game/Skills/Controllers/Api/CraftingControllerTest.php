<?php

namespace Tests\Feature\Game\Skills\Controllers\Api;

use App\Flare\Items\Values\ItemType;
use App\Flare\Models\Character;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateFactionLoyalty;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateNpc;

class CraftingControllerTest extends TestCase
{
    use CreateFactionLoyalty, CreateGameSkill, CreateItem, CreateNpc, RefreshDatabase;

    private ?Character $character = null;

    protected function setUp(): void
    {
        parent::setUp();

        $craftingSkill = $this->createGameSkill([
            'name' => 'Weapon Crafting',
            'type' => SkillTypeValue::CRAFTING->value,
            'max_level' => 400,
        ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->assignFactionSystem()
            ->assignSkill(
                $craftingSkill,
                10
            )
            ->getCharacter();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_fetch_items_to_craft_with_the_ability_to_show_craft_for_npc()
    {
        $npc = $this->createNpc([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $item = $this->createItem([
            'crafting_type' => 'weapon',
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
        ]);

        $factionLoyalty = $this->createFactionLoyalty([
            'faction_id' => $this->character->factions->first()->id,
            'character_id' => $this->character->id,
            'is_pledged' => true,
        ]);

        $factionNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $npc->id,
            'current_level' => 0,
            'max_level' => 25,
            'next_level_fame' => 100,
            'currently_helping' => true,
            'kingdom_item_defence_bonus' => 0.002,
        ]);

        $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id' => $factionLoyalty->id,
            'faction_loyalty_npc_id' => $factionNpc->id,
            'fame_tasks' => [[
                'type' => $item->crafting_type,
                'item_name' => $item->affix_name,
                'item_id' => $item->id,
                'required_amount' => rand(10, 50),
                'current_amount' => 0,
            ]],
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/crafting/'.$this->character->id, [
                'crafting_type' => $item->crafting_type,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals($jsonData['items'][0]['id'], $item->id);
        $this->assertEquals(0, $jsonData['xp']['current_xp']);
        $this->assertTrue($jsonData['show_craft_for_npc']);
    }

    public function test_fetch_items_to_craft_while_not_showing_craft_for_npc_due_to_not_helping_that_npc()
    {
        $npc = $this->createNpc([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $item = $this->createItem([
            'crafting_type' => 'weapon',
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
        ]);

        $factionLoyalty = $this->createFactionLoyalty([
            'faction_id' => $this->character->factions->first()->id,
            'character_id' => $this->character->id,
            'is_pledged' => true,
        ]);

        $factionNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $npc->id,
            'current_level' => 0,
            'max_level' => 25,
            'next_level_fame' => 100,
            'currently_helping' => false,
            'kingdom_item_defence_bonus' => 0.002,
        ]);

        $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id' => $factionLoyalty->id,
            'faction_loyalty_npc_id' => $factionNpc->id,
            'fame_tasks' => [[
                'type' => $item->crafting_type,
                'item_name' => $item->affix_name,
                'item_id' => $item->id,
                'required_amount' => rand(10, 50),
                'current_amount' => 0,
            ]],
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/crafting/'.$this->character->id, [
                'crafting_type' => $item->crafting_type,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals($jsonData['items'][0]['id'], $item->id);
        $this->assertEquals(0, $jsonData['xp']['current_xp']);
        $this->assertFalse($jsonData['show_craft_for_npc']);
    }

    public function test_get_crafting_items()
    {
        $item = $this->createItem([
            'crafting_type' => 'weapon',
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/crafting/'.$this->character->id, [
                'crafting_type' => $item->crafting_type,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals($jsonData['items'][0]['id'], $item->id);
        $this->assertEquals(0, $jsonData['xp']['current_xp']);
        $this->assertFalse($jsonData['show_craft_for_npc']);
    }

    public function test_cannot_craft()
    {
        $item = $this->createItem([
            'crafting_type' => ItemType::DAGGER->value,
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
        ]);

        $this->character->update([
            'can_craft' => false,
        ]);

        $character = $this->character->refresh();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/craft/'.$character->id, [
                'item_to_craft' => $item->id,
                'type' => $item->crafting_type,
                'craft_for_npc' => false,
                'craft_for_event' => false,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('You must wait to craft again.', $jsonData['message']);
        $this->assertEquals(422, $response->status());
    }

    public function test_fetch_paginated_items_to_craft_returns_data_items_and_meta()
    {
        $item = $this->createItem([
            'crafting_type' => 'weapon',
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
            'type' => 'sword',
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/crafting/'.$this->character->id, [
                'crafting_type' => $item->type,
                'per_page' => 10,
                'page' => 1,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('data', $jsonData);
        $this->assertArrayHasKey('items', $jsonData);
        $this->assertArrayHasKey('meta', $jsonData);
        $this->assertArrayHasKey('can_load_more', $jsonData['meta']);
        $this->assertArrayHasKey('pagination', $jsonData['meta']);
        $this->assertSame($jsonData['data'], $jsonData['items']);
    }

    public function test_fetch_paginated_items_with_search_text_filters_results()
    {
        $matchingItem = $this->createItem([
            'name' => 'Iron Sword',
            'crafting_type' => 'weapon',
            'type' => 'sword',
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
        ]);

        $this->createItem([
            'name' => 'Steel Sword',
            'crafting_type' => 'weapon',
            'type' => 'sword',
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/crafting/'.$this->character->id, [
                'crafting_type' => 'sword',
                'per_page' => 10,
                'page' => 1,
                'search_text' => 'Iron',
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $jsonData['data']);
        $this->assertEquals($matchingItem->id, $jsonData['data'][0]['id']);
    }

    public function test_fetch_paginated_armour_items_with_subtype_filter()
    {
        $armourCraftingSkill = $this->createGameSkill([
            'name' => 'Armour Crafting',
            'type' => SkillTypeValue::CRAFTING->value,
            'max_level' => 400,
        ]);

        $this->character->skills()->create([
            'game_skill_id' => $armourCraftingSkill->id,
            'character_id' => $this->character->id,
            'level' => 10,
            'xp' => 0,
            'xp_max' => 100,
            'is_locked' => false,
        ]);

        $helmetItem = $this->createItem([
            'crafting_type' => 'armour',
            'type' => 'helmet',
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
        ]);

        $this->createItem([
            'crafting_type' => 'armour',
            'type' => 'body',
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/crafting/'.$this->character->id, [
                'crafting_type' => 'armour',
                'per_page' => 10,
                'page' => 1,
                'filters' => ['armour_type' => 'helmet'],
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $jsonData['data']);
        $this->assertEquals($helmetItem->id, $jsonData['data'][0]['id']);
    }

    public function test_fetch_paginated_items_for_class_returns_data_items_and_meta()
    {
        $this->createItem([
            'type' => 'sword',
            'crafting_type' => 'weapon',
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/craft-for-class/'.$this->character->id, [
                'per_page' => 10,
                'page' => 1,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('data', $jsonData);
        $this->assertArrayHasKey('items', $jsonData);
        $this->assertArrayHasKey('meta', $jsonData);
        $this->assertArrayHasKey('can_load_more', $jsonData['meta']);
        $this->assertArrayHasKey('pagination', $jsonData['meta']);
        $this->assertSame($jsonData['data'], $jsonData['items']);
    }

    public function test_fetch_paginated_items_with_null_search_text_returns_200()
    {
        $this->createItem([
            'crafting_type' => 'weapon',
            'type' => 'sword',
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/crafting/'.$this->character->id, [
                'crafting_type' => 'sword',
                'per_page' => 10,
                'page' => 1,
                'search_text' => null,
            ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('data', json_decode($response->getContent(), true));
    }

    public function test_fetch_paginated_items_with_null_armour_type_filter_returns_200()
    {
        $armourCraftingSkill = $this->createGameSkill([
            'name' => 'Armour Crafting',
            'type' => SkillTypeValue::CRAFTING->value,
            'max_level' => 400,
        ]);

        $this->character->skills()->create([
            'game_skill_id' => $armourCraftingSkill->id,
            'character_id' => $this->character->id,
            'level' => 10,
            'xp' => 0,
            'xp_max' => 100,
            'is_locked' => false,
        ]);

        $this->createItem([
            'crafting_type' => 'armour',
            'type' => 'helmet',
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/crafting/'.$this->character->id, [
                'crafting_type' => 'armour',
                'per_page' => 10,
                'page' => 1,
                'filters' => ['armour_type' => null],
            ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('data', json_decode($response->getContent(), true));
    }

    public function test_fetch_paginated_items_for_class_with_null_search_text_returns_200()
    {
        $this->createItem([
            'type' => 'sword',
            'crafting_type' => 'weapon',
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/craft-for-class/'.$this->character->id, [
                'per_page' => 10,
                'page' => 1,
                'search_text' => null,
            ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('data', json_decode($response->getContent(), true));
    }

    public function test_craft_item_with_null_search_text_returns_200()
    {
        $item = $this->createItem([
            'type' => ItemType::DAGGER->value,
            'crafting_type' => ItemType::DAGGER->value,
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
        ]);

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $this->character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
        ]);

        $character = $this->character->refresh();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/craft/'.$character->id, [
                'item_to_craft' => $item->id,
                'type' => $item->crafting_type,
                'craft_for_npc' => false,
                'craft_for_event' => false,
                'per_page' => 10,
                'page' => 1,
                'search_text' => null,
                'filters' => [],
            ]);

        $this->assertEquals(200, $response->status());
        $this->assertArrayHasKey('data', json_decode($response->getContent(), true));
    }

    public function test_craft_item()
    {
        $item = $this->createItem([
            'type' => ItemType::DAGGER->value,
            'crafting_type' => ItemType::DAGGER->value,
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
        ]);

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $this->character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
        ]);

        $character = $this->character->refresh();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/craft/'.$character->id, [
                'item_to_craft' => $item->id,
                'type' => $item->crafting_type,
                'craft_for_npc' => false,
                'craft_for_event' => false,
                'per_page' => 10,
                'page' => 1,
                'search_text' => '',
                'filters' => [],
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $character = $this->character->refresh();

        $this->assertEquals(200, $response->status());
        $this->assertArrayHasKey('data', $jsonData);
        $this->assertArrayHasKey('items', $jsonData);
        $this->assertArrayHasKey('meta', $jsonData);
        $this->assertArrayHasKey('can_load_more', $jsonData['meta']);
        $this->assertArrayHasKey('pagination', $jsonData['meta']);
        $this->assertArrayHasKey('crafted_item', $jsonData);
        $this->assertSame($jsonData['data'], $jsonData['items']);
        $this->assertEquals($jsonData['items'][0]['id'], $item->id);
        $this->assertGreaterThan(0, $jsonData['xp']['current_xp']);
        $this->assertFalse($jsonData['show_craft_for_npc']);
        $this->assertNotEmpty($character->inventory->slots);
        $this->assertLessThan(MaxCurrenciesValue::MAX_GOLD, $character->gold);
    }
}
