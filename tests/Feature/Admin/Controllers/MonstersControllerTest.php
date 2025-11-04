<?php

namespace Tests\Feature\Admin\Controllers;

use App\Flare\Models\GameMap;
use App\Flare\Models\Monster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class MonstersControllerTest extends TestCase
{
    use RefreshDatabase, CreateRole, CreateUser, CreateGameMap, CreateMonster, CreateItem;

    private ?GameMap $map = null;

    public function setUp(): void
    {
        parent::setUp();

        $role = $this->createAdminRole();
        $admin = $this->createAdmin($role);
        $this->actingAs($admin);

        $this->map = $this->createGameMap([
            'name' => 'Surface',
            'default' => true,
            'enemy_stat_bonus' => 0.0,
            'only_during_event_type' => null,
        ]);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->map = null;
    }

    public function test_index_displays_monsters_view(): void
    {
        $response = $this->call('GET', route('monsters.list'));

        $this->assertEquals(200, $response->getStatusCode());

        $view = $response->original;

        $this->assertEquals('admin.monsters.monsters', $view->getName());

        $data = $view->getData();

        $this->assertArrayHasKey('gameMapNames', $data);
        $this->assertIsArray($data['gameMapNames']);
    }

    public function test_create_displays_manage_with_form_inputs(): void
    {
        $response = $this->call('GET', route('monsters.create'));

        $this->assertEquals(200, $response->getStatusCode());

        $view = $response->original;

        $this->assertEquals('admin.monsters.manage', $view->getName());

        $data = $view->getData();

        $this->assertArrayHasKey('monster', $data);
        $this->assertArrayHasKey('gameMaps', $data);
        $this->assertArrayHasKey('questItems', $data);
        $this->assertArrayHasKey('specialAttacks', $data);
        $this->assertArrayHasKey('locationTypes', $data);
        $this->assertNull($data['monster']);
    }

    public function test_edit_displays_manage_with_monster(): void
    {
        $monster = $this->createMonster([
            'game_map_id' => $this->map->id,
        ]);

        $response = $this->call('GET', route('monster.edit', ['monster' => $monster->id]));

        $this->assertEquals(200, $response->getStatusCode());

        $view = $response->original;

        $this->assertEquals('admin.monsters.manage', $view->getName());

        $data = $view->getData();

        $this->assertArrayHasKey('monster', $data);
        $this->assertEquals($monster->id, $data['monster']->id);
        $this->assertArrayHasKey('gameMaps', $data);
        $this->assertArrayHasKey('questItems', $data);
        $this->assertArrayHasKey('specialAttacks', $data);
        $this->assertArrayHasKey('locationTypes', $data);
    }

    public function test_show_displays_monster_information_view(): void
    {
        $monster = $this->createMonster([
            'game_map_id' => $this->map->id,
        ]);

        $response = $this->call('GET', route('monsters.monster', ['monster' => $monster->id]));

        $this->assertEquals(200, $response->getStatusCode());

        $view = $response->original;

        $this->assertEquals('admin.monsters.monster', $view->getName());

        $data = $view->getData();

        $this->assertArrayHasKey('monster', $data);
        $this->assertEquals($monster->id, $data['monster']->id);
        $this->assertArrayHasKey('quest', $data);
        $this->assertArrayHasKey('questItem', $data);
    }

    public function test_store_creates_monster_and_redirects_to_show(): void
    {
        $payload = $this->validMonsterPayload([
            'id' => 0,
            'name' => 'Created Beast',
            'game_map_id' => $this->map->id,
            'is_raid_monster' => 0,
            'is_raid_boss' => 0,
        ]);

        $response = $this->call('POST', route('monster.store'), $payload);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue(Monster::where('name', 'Created Beast')->exists());
        $this->assertTrue(session()->has('success'));
    }

    public function test_store_updates_monster_and_redirects_to_show(): void
    {
        $monster = $this->createMonster([
            'game_map_id' => $this->map->id,
            'name' => 'Old Name',
        ]);

        $payload = $this->validMonsterPayload([
            'id' => $monster->id,
            'name' => 'New Name',
            'game_map_id' => $this->map->id,
            'is_raid_monster' => 0,
            'is_raid_boss' => 0,
        ]);

        $response = $this->call('POST', route('monster.store'), $payload);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('New Name', Monster::find($monster->id)->name);
        $this->assertTrue(session()->has('success'));
    }

    public function test_store_with_conflicting_raid_flags_redirects_back_with_error(): void
    {
        $payload = $this->validMonsterPayload([
            'id' => 0,
            'name' => 'Conflict',
            'game_map_id' => $this->map->id,
            'is_raid_monster' => 1,
            'is_raid_boss' => 1,
        ]);

        $response = $this->call('POST', route('monster.store'), $payload, [], [], [
            'HTTP_REFERER' => route('monsters.create'),
        ]);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue(session()->has('error'));
        $this->assertFalse(Monster::where('name', 'Conflict')->exists());
    }

    private function validMonsterPayload(array $overrides = []): array
    {
        $base = [
            'id' => 0,
            'name' => 'Goblin',
            'damage_stat' => 'str',
            'xp' => 10,
            'str' => 10,
            'dur' => 10,
            'dex' => 10,
            'chr' => 10,
            'int' => 10,
            'agi' => 10,
            'focus' => 10,
            'ac' => 0,
            'gold' => 0,
            'max_level' => 10,
            'health_range' => '10-15',
            'attack_range' => '1-3',
            'drop_check' => 0.5,
            'game_map_id' => $this->map?->id ?? 1,
            'is_celestial_entity' => 0,
            'can_cast' => 0,
            'gold_cost' => null,
            'gold_dust_cost' => null,
            'can_use_artifacts' => 0,
            'max_spell_damage' => null,
            'shards' => null,
            'spell_evasion' => 0,
            'affix_resistance' => 0,
            'healing_percentage' => 0,
            'entrancing_chance' => 0,
            'devouring_light_chance' => 0,
            'devouring_darkness_chance' => 0,
            'accuracy' => 0,
            'casting_accuracy' => 0,
            'dodge' => 0,
            'criticality' => 0,
            'quest_item_id' => null,
            'quest_item_drop_chance' => null,
            'only_for_location_type' => null,
            'is_raid_monster' => 0,
            'is_raid_boss' => 0,
        ];

        return array_merge($base, $overrides);
    }
}
