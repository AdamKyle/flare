<?php

namespace Tests\Feature\Admin\Monsters;

use App\Game\Maps\Values\LocationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateMonsterFormPayload;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class MonstersApiControllerTest extends TestCase
{
    use CreateGameMap, CreateItem, CreateMonster, CreateMonsterFormPayload, CreateRole, CreateUser, RefreshDatabase;

    public function test_non_admin_cannot_access_monster_list(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/api/admin/monsters', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_monster_list(): void
    {
        $response = $this->call('GET', '/api/admin/monsters', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(401);
    }

    public function test_list_returns_expected_shape(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap(['name' => 'Monster Map']);
        $this->createMonster(['name' => 'Listed Monster', 'game_map_id' => $gameMap->id, 'xp' => 42]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monsters', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true)['data'];

        $this->assertSame('Listed Monster', $data[0]['name']);
        $this->assertSame('Monster Map', $data[0]['game_map']['name']);
        $this->assertSame(42, $data[0]['xp']);
    }

    public function test_show_returns_static_factual_detail_with_no_combat_scaling(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap(['name' => 'Detail Map']);
        $questItem = $this->createItem(['name' => 'Monster Quest Item', 'type' => 'quest']);
        $monster = $this->createMonster([
            'name' => 'Detail Monster',
            'game_map_id' => $gameMap->id,
            'str' => 15,
            'xp' => 200,
            'quest_item_id' => $questItem->id,
            'quest_item_drop_chance' => 0.125,
            'can_cast' => true,
            'max_spell_damage' => 50,
            'is_raid_monster' => true,
            'fire_atonement' => 0.25,
        ]);

        $response = $this->actingAs($admin)->call('GET', "/api/admin/monsters/{$monster->id}", [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true);

        $this->assertSame('Detail Monster', $data['identity']['name']);
        $this->assertSame('Detail Map', $data['identity']['game_map']['name']);
        $this->assertSame($gameMap->id, $data['identity']['game_map']['id']);
        $this->assertSame(15, $data['combat']['str']);
        $this->assertSame(200, $data['identity']['xp']);
        $this->assertSame('Monster Quest Item', $data['quest_and_celestial']['quest_item']['name']);
        $this->assertSame($questItem->id, $data['quest_and_celestial']['quest_item']['item_id']);
        $this->assertEquals(0.125, $data['quest_and_celestial']['quest_item_drop_chance']);
        $this->assertTrue($data['spells_and_affixes']['can_cast']);
        $this->assertSame(50, $data['spells_and_affixes']['max_spell_damage']);
        $this->assertTrue($data['raid_and_special']['is_raid_monster']);
        $this->assertEquals(0.25, $data['raid_and_special']['fire_atonement']);

        // Static persisted values only — no per-map/combat scaling applied by this contract.
        $this->assertSame($monster->str, $data['combat']['str']);
        $this->assertArrayNotHasKey('can_use_artifacts', $data);
    }

    public function test_store_creates_a_monster_from_every_field_group(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap(['name' => 'Create Map']);
        $questItem = $this->createItem(['name' => 'Store Quest Item', 'type' => 'quest']);

        $payload = $this->minimalMonsterFormPayload($gameMap->id, [
            'name' => 'New Monster',
            'damage_stat' => 'dex',
            'max_level' => 25,
            'xp' => 500,
            'gold' => 250,
            'health_range' => '10-50',
            'attack_range' => '5-20',
            'drop_check' => 7.5,
            'str' => 12,
            'dur' => 13,
            'dex' => 14,
            'chr' => 15,
            'int' => 16,
            'agi' => 17,
            'focus' => 18,
            'ac' => 19,
            'accuracy' => 0.5,
            'dodge' => 0.25,
            'criticality' => 0.1,
            'can_cast' => true,
            'max_spell_damage' => 100,
            'casting_accuracy' => 0.6,
            'quest_item_id' => $questItem->id,
            'quest_item_drop_chance' => 0.4,
            'is_celestial_entity' => true,
            'is_raid_monster' => true,
            'is_raid_boss' => false,
            'raid_special_attack_type' => 0,
            'fire_atonement' => 0.2,
            'ice_atonement' => 0.3,
            'water_atonement' => 0.4,
        ]);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/monsters', $payload, [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(201);
        $this->assertDatabaseHas('monsters', [
            'name' => 'New Monster',
            'game_map_id' => $gameMap->id,
            'damage_stat' => 'dex',
            'max_level' => 25,
            'str' => 12,
            'dex' => 14,
            'ac' => 19,
            'accuracy' => 0.5,
            'can_cast' => true,
            'max_spell_damage' => 100,
            'quest_item_id' => $questItem->id,
            'quest_item_drop_chance' => 0.4,
            'is_celestial_entity' => true,
            'is_raid_monster' => true,
            'is_raid_boss' => false,
            'raid_special_attack_type' => 0,
            'fire_atonement' => 0.2,
        ]);
    }

    public function test_update_modifies_every_field_group(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $monster = $this->createMonster(['name' => 'Editable Monster', 'game_map_id' => $gameMap->id]);
        $questItem = $this->createItem(['name' => 'Update Quest Item', 'type' => 'quest']);

        $payload = $this->minimalMonsterFormPayload($gameMap->id, [
            'name' => 'Renamed Monster',
            'damage_stat' => 'chr',
            'max_level' => 30,
            'xp' => 555,
            'str' => 20,
            'dur' => 21,
            'ac' => 22,
            'accuracy' => 0.75,
            'can_cast' => true,
            'max_spell_damage' => 200,
            'quest_item_id' => $questItem->id,
            'quest_item_drop_chance' => 0.6,
            'is_celestial_entity' => true,
            'is_raid_boss' => true,
            'raid_special_attack_type' => 1,
            'ice_atonement' => 0.9,
        ]);

        $response = $this->actingAs($admin)->call('PUT', "/api/admin/monsters/{$monster->id}", $payload, [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('monsters', [
            'id' => $monster->id,
            'name' => 'Renamed Monster',
            'damage_stat' => 'chr',
            'max_level' => 30,
            'xp' => 555,
            'str' => 20,
            'ac' => 22,
            'accuracy' => 0.75,
            'can_cast' => true,
            'max_spell_damage' => 200,
            'quest_item_id' => $questItem->id,
            'quest_item_drop_chance' => 0.6,
            'is_celestial_entity' => true,
            'is_raid_boss' => true,
            'raid_special_attack_type' => 1,
            'ice_atonement' => 0.9,
        ]);
    }

    public function test_raid_monster_and_raid_boss_are_mutually_exclusive(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();

        $payload = $this->minimalMonsterFormPayload($gameMap->id, [
            'is_raid_monster' => true,
            'is_raid_boss' => true,
        ]);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/monsters', $payload, [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('is_raid_boss');
    }

    public function test_quest_item_drop_chance_normalizes_to_zero_without_a_quest_item(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();

        $payload = $this->minimalMonsterFormPayload($gameMap->id, [
            'quest_item_id' => null,
            'quest_item_drop_chance' => 5,
        ]);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/monsters', $payload, [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(201);
        $response->assertJsonPath('quest_item_drop_chance', 0);
    }

    public function test_list_filters_by_game_map_id(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $mapOne = $this->createGameMap(['name' => 'Map One']);
        $mapTwo = $this->createGameMap(['name' => 'Map Two']);
        $this->createMonster(['name' => 'On Map One', 'game_map_id' => $mapOne->id]);
        $this->createMonster(['name' => 'On Map Two', 'game_map_id' => $mapTwo->id]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monsters', [
            'filters' => ['game_map_id' => $mapOne->id],
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true)['data'];

        $response->assertStatus(200);
        $this->assertCount(1, $data);
        $this->assertSame('On Map One', $data[0]['name']);
    }

    public function test_list_category_filter_regular_excludes_special_monsters(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createMonster(['name' => 'Regular Monster']);
        $this->createMonster(['name' => 'Raid Monster', 'is_raid_monster' => true]);
        $this->createMonster(['name' => 'Raid Boss', 'is_raid_boss' => true]);
        $this->createMonster(['name' => 'Celestial Monster', 'is_celestial_entity' => true]);
        $this->createMonster(['name' => 'Special Location Monster', 'only_for_location_type' => LocationType::CAVE_OF_MEMORIES->value]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monsters', [
            'filters' => ['category' => 'regular'],
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true)['data'];

        $response->assertStatus(200);
        $this->assertCount(1, $data);
        $this->assertSame('Regular Monster', $data[0]['name']);
    }

    public function test_list_category_filter_raid_monster(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createMonster(['name' => 'Regular Monster']);
        $raidMonster = $this->createMonster(['name' => 'Raid Monster', 'is_raid_monster' => true]);
        $this->createMonster(['name' => 'Raid Boss', 'is_raid_boss' => true]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monsters', [
            'filters' => ['category' => 'raid_monster'],
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true)['data'];

        $response->assertStatus(200);
        $this->assertCount(1, $data);
        $this->assertSame($raidMonster->id, $data[0]['id']);
    }

    public function test_list_category_filter_raid_boss(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createMonster(['name' => 'Raid Monster', 'is_raid_monster' => true]);
        $raidBoss = $this->createMonster(['name' => 'Raid Boss', 'is_raid_boss' => true]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monsters', [
            'filters' => ['category' => 'raid_boss'],
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true)['data'];

        $response->assertStatus(200);
        $this->assertCount(1, $data);
        $this->assertSame($raidBoss->id, $data[0]['id']);
    }

    public function test_list_category_filter_celestial(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $celestial = $this->createMonster(['name' => 'Celestial Monster', 'is_celestial_entity' => true]);
        $this->createMonster(['name' => 'Regular Monster']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monsters', [
            'filters' => ['category' => 'celestial'],
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true)['data'];

        $response->assertStatus(200);
        $this->assertCount(1, $data);
        $this->assertSame($celestial->id, $data[0]['id']);
    }

    public function test_list_category_filter_special_location_includes_cave_of_memories(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $caveMonster = $this->createMonster([
            'name' => 'Cave Monster',
            'only_for_location_type' => LocationType::CAVE_OF_MEMORIES->value,
        ]);
        $this->createMonster(['name' => 'Regular Monster']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monsters', [
            'filters' => ['category' => 'special_location'],
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true)['data'];

        $response->assertStatus(200);
        $this->assertCount(1, $data);
        $this->assertSame($caveMonster->id, $data[0]['id']);
    }

    public function test_list_category_filter_weekly_fight_excludes_cave_of_memories(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createMonster([
            'name' => 'Cave Monster',
            'only_for_location_type' => LocationType::CAVE_OF_MEMORIES->value,
        ]);
        $weeklyMonster = $this->createMonster([
            'name' => 'Alchemy Church Monster',
            'only_for_location_type' => LocationType::ALCHEMY_CHURCH->value,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monsters', [
            'filters' => ['category' => 'weekly_fight'],
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true)['data'];

        $response->assertStatus(200);
        $this->assertCount(1, $data);
        $this->assertSame($weeklyMonster->id, $data[0]['id']);
    }

    public function test_list_special_location_category_with_exact_location_type(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $caveMonster = $this->createMonster([
            'name' => 'Cave Monster',
            'only_for_location_type' => LocationType::CAVE_OF_MEMORIES->value,
        ]);
        $this->createMonster([
            'name' => 'Alchemy Church Monster',
            'only_for_location_type' => LocationType::ALCHEMY_CHURCH->value,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monsters', [
            'filters' => [
                'category' => 'special_location',
                'location_type' => LocationType::CAVE_OF_MEMORIES->value,
            ],
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true)['data'];

        $response->assertStatus(200);
        $this->assertCount(1, $data);
        $this->assertSame($caveMonster->id, $data[0]['id']);
    }

    public function test_list_weekly_fight_category_with_valid_weekly_type(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $alchemyMonster = $this->createMonster([
            'name' => 'Alchemy Church Monster',
            'only_for_location_type' => LocationType::ALCHEMY_CHURCH->value,
        ]);
        $this->createMonster([
            'name' => 'Broken Anvil Monster',
            'only_for_location_type' => LocationType::BROKEN_ANVIL->value,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monsters', [
            'filters' => [
                'category' => 'weekly_fight',
                'location_type' => LocationType::ALCHEMY_CHURCH->value,
            ],
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true)['data'];

        $response->assertStatus(200);
        $this->assertCount(1, $data);
        $this->assertSame($alchemyMonster->id, $data[0]['id']);
    }

    public function test_list_rejects_invalid_location_type(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monsters', [
            'filters' => [
                'category' => 'special_location',
                'location_type' => 999,
            ],
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('filters.location_type');
    }

    public function test_list_rejects_location_type_for_regular_category(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monsters', [
            'filters' => [
                'category' => 'regular',
                'location_type' => LocationType::ALCHEMY_CHURCH->value,
            ],
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('filters.location_type');
    }

    public function test_list_composes_search_map_category_location_and_sort_filters(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap(['name' => 'Weekly Map']);
        $otherMap = $this->createGameMap(['name' => 'Other Map']);

        $matching = $this->createMonster([
            'name' => 'Zeta Weekly Fighter',
            'game_map_id' => $gameMap->id,
            'only_for_location_type' => LocationType::LORDS_STRONG_HOLD->value,
        ]);
        $this->createMonster([
            'name' => 'Alpha Weekly Fighter',
            'game_map_id' => $otherMap->id,
            'only_for_location_type' => LocationType::LORDS_STRONG_HOLD->value,
        ]);
        $this->createMonster([
            'name' => 'Zeta Weekly Fighter Two',
            'game_map_id' => $gameMap->id,
            'only_for_location_type' => LocationType::ALCHEMY_CHURCH->value,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monsters', [
            'search_text' => 'Zeta Weekly Fighter',
            'sort_key' => 'name',
            'sort_direction' => 'asc',
            'filters' => [
                'game_map_id' => $gameMap->id,
                'category' => 'weekly_fight',
                'location_type' => LocationType::LORDS_STRONG_HOLD->value,
            ],
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true)['data'];

        $response->assertStatus(200);
        $this->assertCount(1, $data);
        $this->assertSame($matching->id, $data[0]['id']);
    }
}
