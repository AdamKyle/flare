<?php

namespace Tests\Feature\Game\Monsters\Controllers\Api;

use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\MapNameValue;
use App\Game\Monsters\Services\BuildMonsterCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;

class MonstersControllerTest extends TestCase
{
    use CreateGameMap, CreateItem, CreateLocation, CreateMonster, RefreshDatabase;

    private ?CharacterFactory $characterFactory = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    protected function tearDown(): void
    {
        $this->characterFactory = null;

        parent::tearDown();
    }

    public function test_basic_map_returns_base_stat()
    {
        $surface = $this->createGameMap(['name' => MapNameValue::SURFACE, 'default' => true]);

        $character = $this->characterFactory->getCharacter();
        $character->map()->update(['game_map_id' => $surface->id]);
        $character = $character->refresh();

        $this->createSession($character->user->id);

        $monster = $this->createMonster([
            'game_map_id' => $surface->id,
            'str' => 10,
            'health_range' => '10-20',
            'attack_range' => '1-3',
        ]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $response = $this->actingAs($character->user)->call('GET', '/api/monster-stat/'.$monster->id.'/'.$character->id);

        $json = json_decode($response->getContent(), true);

        $this->assertSame(10, $json['str']);
    }

    public function test_location_flat_increase_on_special_map_applies_to_stats()
    {
        $hell = $this->createGameMap([
            'name' => MapNameValue::HELL,
            'default' => false,
            'enemy_stat_bonus' => 0.10,
        ]);

        $character = $this->characterFactory->getCharacter();
        $character->map()->update(['game_map_id' => $hell->id]);
        $character = $character->refresh();

        $this->createSession($character->user->id);

        $monster = $this->createMonster([
            'game_map_id' => $hell->id,
            'str' => 10,
            'health_range' => '10-20',
            'attack_range' => '1-3',
        ]);

        $position = $character->map;

        $this->createLocation([
            'name' => 'Lava Ridge',
            'x' => $position->character_position_x,
            'y' => $position->character_position_y,
            'game_map_id' => $position->game_map_id,
            'enemy_strength_increase' => 2,
        ]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $response = $this->actingAs($character->user)->call('GET', '/api/monster-stat/'.$monster->id.'/'.$character->id);
        $json = json_decode($response->getContent(), true);

        $this->assertSame(12, $json['str']);
    }

    public function test_location_flat_increase_on_basic_map_applies_to_stats()
    {
        $surface = $this->createGameMap(['name' => MapNameValue::SURFACE, 'default' => true]);

        $character = $this->characterFactory->getCharacter();
        $character->map()->update(['game_map_id' => $surface->id]);
        $character = $character->refresh();

        $this->createSession($character->user->id);

        $monster = $this->createMonster([
            'game_map_id' => $surface->id,
            'str' => 10,
            'health_range' => '10-20',
            'attack_range' => '1-3',
        ]);

        $position = $character->map;

        $this->createLocation([
            'name' => 'Ruins',
            'x' => $position->character_position_x,
            'y' => $position->character_position_y,
            'game_map_id' => $position->game_map_id,
            'enemy_strength_increase' => 2,
        ]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $response = $this->actingAs($character->user)->call('GET', '/api/monster-stat/'.$monster->id.'/'.$character->id);
        $json = json_decode($response->getContent(), true);

        $this->assertSame(12, $json['str']);
    }

    public function test_regular_monster_flag_is_returned()
    {
        $surface = $this->createGameMap(['name' => MapNameValue::SURFACE, 'default' => true]);

        $character = $this->characterFactory->getCharacter();
        $character->map()->update(['game_map_id' => $surface->id]);
        $character = $character->refresh();

        $this->createSession($character->user->id);

        $monster = $this->createMonster([
            'game_map_id' => $surface->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
            'health_range' => '10-20',
            'attack_range' => '1-3',
        ]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $response = $this->actingAs($character->user)->call('GET', '/api/monster-stat/'.$monster->id.'/'.$character->id);
        $json = json_decode($response->getContent(), true);

        $this->assertFalse($json['is_raid_monster']);
    }

    public function test_raid_monster_returns_error_when_not_in_list()
    {
        $surface = $this->createGameMap(['name' => MapNameValue::SURFACE, 'default' => true]);

        $character = $this->characterFactory->getCharacter();
        $character->map()->update(['game_map_id' => $surface->id]);
        $character = $character->refresh();

        $this->createSession($character->user->id);

        $raid = $this->createMonster([
            'game_map_id' => $surface->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => true,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
            'health_range' => '10-20',
            'attack_range' => '1-3',
        ]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $response = $this->actingAs($character->user)->call('GET', '/api/monster-stat/'.$raid->id.'/'.$character->id);
        $json = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('message', $json);
    }

    public function test_special_map_with_purgatory_uses_regular_dataset()
    {
        $surface = $this->createGameMap(['name' => MapNameValue::SURFACE, 'default' => true]);

        $ice = $this->createGameMap([
            'name' => MapNameValue::ICE_PLANE,
            'default' => false,
            'enemy_stat_bonus' => 0.10,
            'only_during_event_type' => 1,
        ]);

        $character = $this->characterFactory->getCharacter();
        $character->map()->update(['game_map_id' => $ice->id]);
        $character = $character->refresh();

        $this->createSession($character->user->id);

        $regular = $this->createMonster([
            'game_map_id' => $ice->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
            'name' => 'Ice Regular',
            'health_range' => '10-20',
            'attack_range' => '1-3',
        ]);

        $this->createMonster([
            'game_map_id' => $surface->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
            'name' => 'Surface Easier',
            'health_range' => '10-20',
            'attack_range' => '1-3',
        ]);

        $purgatoryItem = $this->createItem([
            'name' => 'Purgatory Token',
            'effect' => ItemEffectsValue::PURGATORY,
        ]);
        $this->characterFactory->inventoryManagement()->giveItem($purgatoryItem);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/monster-stat/'.$regular->id.'/'.$character->id);

        $json = json_decode($response->getContent(), true);

        $this->assertSame('Ice Regular', $json['name']);
    }

    public function test_base_drop_chance_without_increase()
    {
        $surface = $this->createGameMap(['name' => MapNameValue::SURFACE, 'default' => true]);

        $character = $this->characterFactory->getCharacter();
        $character->map()->update(['game_map_id' => $surface->id]);
        $character = $character->refresh();

        $this->createSession($character->user->id);

        $monster = $this->createMonster([
            'game_map_id' => $surface->id,
            'drop_check' => 0.42,
            'health_range' => '10-20',
            'attack_range' => '1-3',
        ]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $response = $this->actingAs($character->user)->call('GET', '/api/monster-stat/'.$monster->id.'/'.$character->id);
        $json = json_decode($response->getContent(), true);

        $this->assertEquals(0.42, $json['drop_chance']);
    }

    public function test_drop_chance_ignores_location_increase_in_stats_endpoint()
    {
        $hell = $this->createGameMap([
            'name' => MapNameValue::HELL,
            'default' => false,
            'enemy_stat_bonus' => 0.10,
        ]);

        $character = $this->characterFactory->getCharacter();
        $character->map()->update(['game_map_id' => $hell->id]);
        $character = $character->refresh();

        $this->createSession($character->user->id);

        $monster = $this->createMonster([
            'game_map_id' => $hell->id,
            'drop_check' => 0.80,
            'health_range' => '10-20',
            'attack_range' => '1-3',
        ]);

        $position = $character->map;

        $this->createLocation([
            'name' => 'Lava Ridge',
            'x' => $position->character_position_x,
            'y' => $position->character_position_y,
            'game_map_id' => $position->game_map_id,
            'enemy_strength_increase' => 2,
        ]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $response = $this->actingAs($character->user)->call('GET', '/api/monster-stat/'.$monster->id.'/'.$character->id);
        $json = json_decode($response->getContent(), true);

        $this->assertEquals(0.80, $json['drop_chance']);
    }

    public function test_location_increase_does_not_change_drop_chance_on_surface()
    {
        $surface = $this->createGameMap(['name' => MapNameValue::SURFACE, 'default' => true]);

        $character = $this->characterFactory->getCharacter();
        $character->map()->update(['game_map_id' => $surface->id]);
        $character = $character->refresh();

        $this->createSession($character->user->id);

        $monster = $this->createMonster([
            'game_map_id' => $surface->id,
            'drop_check' => 0.33,
            'health_range' => '10-20',
            'attack_range' => '1-3',
        ]);

        $position = $character->map;

        $this->createLocation([
            'name' => 'Ruins',
            'x' => $position->character_position_x,
            'y' => $position->character_position_y,
            'game_map_id' => $position->game_map_id,
            'enemy_strength_increase' => 2,
        ]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $response = $this->actingAs($character->user)->call('GET', '/api/monster-stat/'.$monster->id.'/'.$character->id);
        $json = json_decode($response->getContent(), true);

        $this->assertEquals(0.33, $json['drop_chance']);
    }

    public function test_drop_chance_capped_at_ninety_nine_in_endpoint()
    {
        $surface = $this->createGameMap(['name' => MapNameValue::SURFACE, 'default' => true]);

        $character = $this->characterFactory->getCharacter();
        $character->map()->update(['game_map_id' => $surface->id]);
        $character = $character->refresh();

        $this->createSession($character->user->id);

        $monster = $this->createMonster([
            'game_map_id' => $surface->id,
            'drop_check' => 0.995,
            'health_range' => '10-20',
            'attack_range' => '1-3',
        ]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $response = $this->actingAs($character->user)->call('GET', '/api/monster-stat/'.$monster->id.'/'.$character->id);
        $json = json_decode($response->getContent(), true);

        $this->assertEquals(0.99, $json['drop_chance']);
    }

    public function test_special_map_with_purgatory_uses_regular_dataset_drop_chance()
    {
        $surface = $this->createGameMap(['name' => MapNameValue::SURFACE, 'default' => true]);

        $ice = $this->createGameMap([
            'name' => MapNameValue::ICE_PLANE,
            'default' => false,
            'enemy_stat_bonus' => 0.10,
            'only_during_event_type' => 1,
        ]);

        $character = $this->characterFactory->getCharacter();
        $character->map()->update(['game_map_id' => $ice->id]);
        $character = $character->refresh();

        $this->createSession($character->user->id);

        $monster = $this->createMonster([
            'game_map_id' => $ice->id,
            'drop_check' => 0.4,
            'health_range' => '10-20',
            'attack_range' => '1-3',
            'name' => 'Ice Regular',
        ]);

        $this->createMonster([
            'game_map_id' => $surface->id,
            'drop_check' => 0.2,
            'health_range' => '10-20',
            'attack_range' => '1-3',
        ]);

        $purgatoryItem = $this->createItem([
            'name' => 'Purgatory Token',
            'effect' => ItemEffectsValue::PURGATORY,
        ]);
        $this->characterFactory->inventoryManagement()->giveItem($purgatoryItem);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/monster-stat/'.$monster->id.'/'.$character->id);

        $json = json_decode($response->getContent(), true);

        $this->assertEquals(0.4, $json['drop_chance']);
    }

    public function test_list_monsters_returns_regular_for_characters_map()
    {
        $surface = $this->createGameMap(['name' => MapNameValue::SURFACE, 'default' => true]);
        $hell = $this->createGameMap(['name' => MapNameValue::HELL, 'default' => false]);

        $character = $this->characterFactory->getCharacter();
        $character->map()->update(['game_map_id' => $surface->id]);
        $character = $character->refresh();

        $this->createSession($character->user->id);

        $monsterA = $this->createMonster([
            'game_map_id' => $surface->id,
            'name' => 'Surface Slime',
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
            'health_range' => '5-7',
            'attack_range' => '1-2',
        ]);

        $monsterB = $this->createMonster([
            'game_map_id' => $surface->id,
            'name' => 'Surface Goblin',
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
            'health_range' => '8-12',
            'attack_range' => '1-3',
        ]);

        $this->createMonster([
            'game_map_id' => $hell->id,
            'name' => 'Hell Imp',
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
            'health_range' => '10-15',
            'attack_range' => '2-4',
        ]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $response = $this->actingAs($character->user)->call('GET', '/api/monster-list/'.$character->id);
        $json = json_decode($response->getContent(), true);

        $names = collect($json)->pluck('name')->all();

        $this->assertContains($monsterA->name, $names);
        $this->assertContains($monsterB->name, $names);
        $this->assertNotContains('Hell Imp', $names);
    }

    public function test_list_monsters_excludes_raid_and_celestial()
    {
        $surface = $this->createGameMap(['name' => MapNameValue::SURFACE, 'default' => true]);

        $character = $this->characterFactory->getCharacter();
        $character->map()->update(['game_map_id' => $surface->id]);
        $character = $character->refresh();

        $this->createSession($character->user->id);

        $regular = $this->createMonster([
            'game_map_id' => $surface->id,
            'name' => 'Bandit',
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
            'health_range' => '10-20',
            'attack_range' => '1-3',
        ]);

        $this->createMonster([
            'game_map_id' => $surface->id,
            'name' => 'Raid Add',
            'is_celestial_entity' => false,
            'is_raid_monster' => true,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
            'health_range' => '20-30',
            'attack_range' => '3-6',
        ]);

        $this->createMonster([
            'game_map_id' => $surface->id,
            'name' => 'Fallen Angel',
            'is_celestial_entity' => true,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
            'health_range' => '40-60',
            'attack_range' => '5-10',
        ]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $response = $this->actingAs($character->user)->call('GET', '/api/monster-list/'.$character->id);
        $json = json_decode($response->getContent(), true);

        $names = collect($json)->pluck('name')->all();

        $this->assertContains($regular->name, $names);
        $this->assertNotContains('Raid Add', $names);
        $this->assertNotContains('Fallen Angel', $names);
    }

    private function createSession(int $userId): void
    {
        DB::table('sessions')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $userId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'testing',
            'payload' => '',
            'last_activity' => time(),
        ]);
    }
}
