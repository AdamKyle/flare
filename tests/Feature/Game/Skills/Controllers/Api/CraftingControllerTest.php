<?php

namespace Tests\Feature\Game\Skills\Controllers\Api;

use App\Flare\Models\ItemSkill;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class CraftingControllerTest extends TestCase
{
    use CreateGameSkill, CreateItem, RefreshDatabase;

    protected function tearDown(): void
    {
        Model::preventLazyLoading(false);

        parent::tearDown();
    }

    public function test_fetch_items_to_craft_returns_condensed_rows_with_nested_preview(): void
    {
        $craftingSkill = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($craftingSkill)
            ->givePlayerLocation()
            ->getCharacter();

        $this->createItem([
            'cost' => 100,
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'crafting_type' => 'weapon',
            'type' => 'weapon',
            'can_craft' => true,
            'default_position' => 'hammer',
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/crafting/'.$character->id, ['crafting_type' => 'hammer']);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertNotEmpty($data['items']);
        $item = $data['items'][0];
        $this->assertArrayHasKey('preview', $item);
        $this->assertArrayHasKey('item_id', $item['preview']);
        $this->assertArrayHasKey('id', $item);
        $this->assertArrayHasKey('cost', $item);
        $this->assertArrayNotHasKey('name', $item);
    }

    public function test_fetch_items_to_craft_returns_armour_item_with_stored_name_and_type(): void
    {
        $craftingSkill = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($craftingSkill)
            ->givePlayerLocation()
            ->getCharacter();

        $this->createItem([
            'name' => 'Reinforced Plate Armour',
            'cost' => 250,
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'crafting_type' => 'armour',
            'type' => 'body',
            'can_craft' => true,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/crafting/'.$character->id, ['crafting_type' => 'armour']);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $row = $data['items'][0];
        $this->assertIsInt($row['id']);
        $this->assertIsNumeric($row['cost']);
        $this->assertIsArray($row['preview']);
        $this->assertSame('Reinforced Plate Armour', $row['preview']['name']);
        $this->assertSame('body', $row['preview']['type']);
    }

    public function test_fetch_items_to_craft_returns_spell_item_with_stored_name_and_type(): void
    {
        $craftingSkill = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($craftingSkill)
            ->givePlayerLocation()
            ->getCharacter();

        $this->createItem([
            'name' => 'Searing Bolt',
            'cost' => 180,
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'crafting_type' => 'spell',
            'type' => 'spell-damage',
            'can_craft' => true,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/crafting/'.$character->id, ['crafting_type' => 'spell']);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $row = $data['items'][0];
        $this->assertIsInt($row['id']);
        $this->assertIsNumeric($row['cost']);
        $this->assertIsArray($row['preview']);
        $this->assertSame('Searing Bolt', $row['preview']['name']);
        $this->assertSame('spell-damage', $row['preview']['type']);
    }

    public function test_fetch_items_to_craft_returns_ring_item_with_stored_name_and_type(): void
    {
        $craftingSkill = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($craftingSkill)
            ->givePlayerLocation()
            ->getCharacter();

        $this->createItem([
            'name' => 'Band of Fortitude',
            'cost' => 300,
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'crafting_type' => 'ring',
            'type' => 'ring',
            'can_craft' => true,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/crafting/'.$character->id, ['crafting_type' => 'ring']);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $row = $data['items'][0];
        $this->assertIsInt($row['id']);
        $this->assertIsNumeric($row['cost']);
        $this->assertIsArray($row['preview']);
        $this->assertSame('Band of Fortitude', $row['preview']['name']);
        $this->assertSame('ring', $row['preview']['type']);
    }

    public function test_fetch_items_for_class_returns_class_specific_weapon_with_stored_name(): void
    {
        $craftingSkill = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value]);

        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['name' => 'Fighter'])
            ->assignSkill($craftingSkill)
            ->givePlayerLocation()
            ->getCharacter();

        $this->createItem([
            'name' => 'Gladius of the Fallen',
            'cost' => 220,
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'crafting_type' => 'weapon',
            'type' => 'sword',
            'can_craft' => true,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/craft-for-class/'.$character->id);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $row = $data['items'][0];
        $this->assertIsInt($row['id']);
        $this->assertIsNumeric($row['cost']);
        $this->assertIsArray($row['preview']);
        $this->assertSame('Gladius of the Fallen', $row['preview']['name']);
        $this->assertSame('sword', $row['preview']['type']);
    }

    public function test_fetch_items_to_craft_paginated_returns_canonical_pagination_envelope(): void
    {
        $craftingSkill = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($craftingSkill)
            ->givePlayerLocation()
            ->getCharacter();

        $this->createItem([
            'cost' => 100,
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'crafting_type' => 'weapon',
            'type' => 'weapon',
            'can_craft' => true,
            'default_position' => 'hammer',
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/crafting/'.$character->id, [
                'crafting_type' => 'hammer',
                'per_page' => 5,
                'page' => 1,
            ]);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('can_load_more', $data['meta']);
        $this->assertArrayHasKey('pagination', $data['meta']);
        $this->assertArrayHasKey('preview', $data['items'][0]);
    }

    public function test_fetch_items_to_craft_paginated_with_damage_spell_item_type_filter_excludes_healing_spells(): void
    {
        $craftingSkill = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($craftingSkill)
            ->givePlayerLocation()
            ->getCharacter();

        $this->createItem([
            'name' => 'Searing Bolt',
            'cost' => 100,
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'crafting_type' => 'spell',
            'type' => 'spell-damage',
            'can_craft' => true,
        ]);

        $this->createItem([
            'name' => 'Mending Light',
            'cost' => 100,
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'crafting_type' => 'spell',
            'type' => 'spell-healing',
            'can_craft' => true,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/crafting/'.$character->id, [
                'crafting_type' => 'spell',
                'per_page' => 15,
                'page' => 1,
                'filters' => ['item_type' => 'spell-damage'],
            ]);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertCount(1, $data['data']);
        $this->assertSame('Searing Bolt', $data['data'][0]['preview']['name']);
        $this->assertArrayHasKey('pagination', $data['meta']);
    }

    public function test_fetch_items_to_craft_paginated_with_healing_spell_item_type_filter_excludes_damage_spells(): void
    {
        $craftingSkill = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($craftingSkill)
            ->givePlayerLocation()
            ->getCharacter();

        $this->createItem([
            'name' => 'Searing Bolt',
            'cost' => 100,
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'crafting_type' => 'spell',
            'type' => 'spell-damage',
            'can_craft' => true,
        ]);

        $this->createItem([
            'name' => 'Mending Light',
            'cost' => 100,
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'crafting_type' => 'spell',
            'type' => 'spell-healing',
            'can_craft' => true,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/crafting/'.$character->id, [
                'crafting_type' => 'spell',
                'per_page' => 15,
                'page' => 1,
                'filters' => ['item_type' => 'spell-healing'],
            ]);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertCount(1, $data['data']);
        $this->assertSame('Mending Light', $data['data'][0]['preview']['name']);
        $this->assertArrayHasKey('pagination', $data['meta']);
    }

    public function test_fetch_items_to_craft_paginated_without_item_type_filter_returns_both_spell_types(): void
    {
        $craftingSkill = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($craftingSkill)
            ->givePlayerLocation()
            ->getCharacter();

        $this->createItem([
            'name' => 'Searing Bolt',
            'cost' => 100,
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'crafting_type' => 'spell',
            'type' => 'spell-damage',
            'can_craft' => true,
        ]);

        $this->createItem([
            'name' => 'Mending Light',
            'cost' => 100,
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'crafting_type' => 'spell',
            'type' => 'spell-healing',
            'can_craft' => true,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/crafting/'.$character->id, [
                'crafting_type' => 'spell',
                'per_page' => 15,
                'page' => 1,
            ]);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertCount(2, $data['data']);
    }

    public function test_craft_success_returns_result_preview_from_created_inventory_slot(): void
    {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $craftingSkill = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($craftingSkill)
            ->givePlayerLocation()
            ->inventoryManagement()
            ->getCharacter();

        $character->update(['gold' => 1000000]);

        $item = $this->createItem([
            'cost' => 100,
            'skill_level_required' => 1,
            'skill_level_trivial' => 50,
            'crafting_type' => 'weapon',
            'type' => 'weapon',
            'can_craft' => true,
            'default_position' => 'hammer',
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/craft/'.$character->id, [
                'item_to_craft' => $item->id,
                'type' => 'hammer',
                'craft_for_npc' => false,
                'craft_for_event' => false,
            ]);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertTrue($data['crafted_item']);
        $this->assertNotNull($data['result_preview']);
        $this->assertSame($item->id, $data['result_preview']['item_id']);
        $this->assertSame($data['crafted_inventory_slot_id'], $data['result_preview']['inventory_slot_id']);
    }

    public function test_craft_failure_returns_null_result_preview(): void
    {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(100);
                $mock->shouldReceive('characterRoll')->once()->andReturn(1);
            })
        );

        $craftingSkill = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($craftingSkill)
            ->givePlayerLocation()
            ->inventoryManagement()
            ->getCharacter();

        $character->update(['gold' => 1000000]);

        $item = $this->createItem([
            'cost' => 100,
            'skill_level_required' => 1,
            'skill_level_trivial' => 50,
            'crafting_type' => 'weapon',
            'type' => 'weapon',
            'can_craft' => true,
            'default_position' => 'hammer',
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/craft/'.$character->id, [
                'item_to_craft' => $item->id,
                'type' => 'hammer',
                'craft_for_npc' => false,
                'craft_for_event' => false,
            ]);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertFalse($data['crafted_item']);
        $this->assertNull($data['result_preview']);
    }

    public function test_fetch_items_to_craft_paginated_with_populated_rows_does_not_lazy_load(): void
    {
        $craftingSkill = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($craftingSkill)
            ->givePlayerLocation()
            ->getCharacter();

        $itemSkill = ItemSkill::create([
            'name' => 'Hammer Mastery',
            'description' => 'Increases hammer proficiency.',
            'max_level' => 10,
            'total_kills_needed' => 100,
        ]);

        $itemWithProgression = $this->createItem([
            'cost' => 100,
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'crafting_type' => 'weapon',
            'type' => 'weapon',
            'can_craft' => true,
            'default_position' => 'hammer',
        ]);

        $itemWithProgression->itemSkillProgressions()->create([
            'item_id' => $itemWithProgression->id,
            'item_skill_id' => $itemSkill->id,
            'current_level' => 1,
            'current_kill' => 10,
            'is_training' => false,
        ]);

        $this->createItem([
            'cost' => 150,
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'crafting_type' => 'weapon',
            'type' => 'weapon',
            'can_craft' => true,
            'default_position' => 'hammer',
        ]);

        Model::preventLazyLoading();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/crafting/'.$character->id, [
                'crafting_type' => 'hammer',
                'per_page' => 15,
                'page' => 1,
            ]);

        $response->assertOk();

        $data = json_decode($response->getContent(), true);

        $this->assertCount(2, $data['data']);
        $this->assertArrayHasKey('preview', $data['data'][0]);
    }
}
