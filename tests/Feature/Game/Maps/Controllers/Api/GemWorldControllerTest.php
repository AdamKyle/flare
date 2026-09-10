<?php

namespace Tests\Feature\Game\Maps\Controllers\Api;

use App\Flare\GemWorldGeneration\Values\GeneratedGemMapType;
use App\Game\Gems\Services\AreaGemEffectService;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Monsters\Services\BuildMonsterCacheService;
use App\Game\Monsters\Values\MonsterCacheKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameLocationGemParamter;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameMapGemParamter;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;

class GemWorldControllerTest extends TestCase
{
    use CreateGameLocationGemParamter, CreateGameMap, CreateGameMapGemParamter, CreateGem, CreateLocation, CreateMonster, RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_context_returns_map_gem_entry_when_character_is_not_standing_on_a_location(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);
        $mapGemParamter = $this->createGameMapGemParamter(['game_map_id' => $parentMap->id, 'name' => 'Fiery']);
        $mapGem = $this->createMapGeneratedGem($mapGemParamter, ['enemy_strength_increase' => 0.4]);
        $mapGemParamter->update(['rolled_gem_id' => $mapGem->id]);
        $generatedMap = $this->createGameMap([
            'name' => 'Fiery Map Gem World',
            'generated_map_type' => GeneratedGemMapType::MAP_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_map_gem_paramter_id' => $mapGemParamter->id,
            'can_traverse' => false,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $parentMap)->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/map/gem-world/'.$character->id);

        $data = json_decode($response->getContent(), true);

        $expectedEffects = (new AreaGemEffectService)->resolveForGameMap($generatedMap->fresh());

        $response->assertOk();
        $this->assertFalse($data['inside_gem_world']);
        $this->assertSame('map_gem', $data['entry']['type']);
        $this->assertSame('Enter Map Gem', $data['entry']['label']);
        $this->assertSame($generatedMap->id, $data['entry']['generated_game_map']['id']);
        $this->assertSame('map_gem_world', $data['entry']['context']['type']);
        $this->assertNotEmpty($data['entry']['context']['rules']);
        $this->assertSame('map_gem', $data['entry']['context']['sources'][0]['type']);
        $this->assertSame($mapGem->id, $data['entry']['context']['sources'][0]['rolled_gem']['id']);
        $this->assertSame($mapGem->name, $data['entry']['context']['sources'][0]['rolled_gem']['name']);
        $this->assertSame(
            $expectedEffects->monsterEffects()->toArray()['enemy_strength_increase'],
            $data['entry']['context']['monster_effects']['enemy_strength_increase'],
        );
    }

    public function test_context_location_gem_wins_entry_eligibility_over_an_eligible_map_gem(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);
        $mapGemParamter = $this->createGameMapGemParamter(['game_map_id' => $parentMap->id, 'name' => 'Fiery']);
        $mapGem = $this->createMapGeneratedGem($mapGemParamter);
        $mapGemParamter->update(['rolled_gem_id' => $mapGem->id]);
        $this->createGameMap([
            'name' => 'Fiery Map Gem World',
            'generated_map_type' => GeneratedGemMapType::MAP_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_map_gem_paramter_id' => $mapGemParamter->id,
            'can_traverse' => false,
        ]);

        $location = $this->createLocation(['game_map_id' => $parentMap->id, 'x' => 50, 'y' => 50]);
        $locationGemParamter = $this->createGameLocationGemParamter(['location_id' => $location->id, 'name' => 'Watery']);
        $locationGem = $this->createLocationGeneratedGem($locationGemParamter, ['enemy_strength_increase' => 0.5]);
        $locationGemParamter->update(['rolled_gem_id' => $locationGem->id]);
        $generatedLocationMap = $this->createGameMap([
            'name' => 'Watery Location Gem World',
            'generated_map_type' => GeneratedGemMapType::LOCATION_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_location_gem_paramter_id' => $locationGemParamter->id,
            'can_traverse' => false,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(50, 50, $parentMap)->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/map/gem-world/'.$character->id);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertSame('location_gem', $data['entry']['type']);
        $this->assertSame('Enter Location Gem', $data['entry']['label']);
        $this->assertSame($generatedLocationMap->id, $data['entry']['generated_game_map']['id']);
        $this->assertSame('location_gem_world', $data['entry']['context']['type']);
        $locationSource = collect($data['entry']['context']['sources'])->firstWhere('type', 'location_gem');
        $this->assertNotNull($locationSource);
        $this->assertSame($locationGem->id, $locationSource['rolled_gem']['id']);
    }

    public function test_context_standing_on_location_without_eligible_location_gem_blocks_map_entry_fallback(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);
        $mapGemParamter = $this->createGameMapGemParamter(['game_map_id' => $parentMap->id, 'name' => 'Fiery']);
        $mapGem = $this->createMapGeneratedGem($mapGemParamter, ['enemy_strength_increase' => 0.4]);
        $mapGemParamter->update(['rolled_gem_id' => $mapGem->id]);
        $this->createGameMap([
            'name' => 'Fiery Map Gem World',
            'generated_map_type' => GeneratedGemMapType::MAP_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_map_gem_paramter_id' => $mapGemParamter->id,
            'can_traverse' => false,
        ]);

        $location = $this->createLocation(['game_map_id' => $parentMap->id, 'x' => 60, 'y' => 60]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(60, 60, $parentMap)->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/map/gem-world/'.$character->id);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertNull($data['entry']);
        $this->assertNotNull($data['current_context']);
        $this->assertSame('location', $data['current_context']['type']);
    }

    public function test_context_returns_no_entry_or_current_context_when_no_gems_exist(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $parentMap)->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/map/gem-world/'.$character->id);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertNull($data['entry']);
        $this->assertNull($data['current_context']);
        $this->assertFalse($data['inside_gem_world']);
        $this->assertNull($data['exit']);
    }

    public function test_context_inside_a_generated_map_gem_world_exposes_no_entry_and_exact_exit(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);
        $mapGemParamter = $this->createGameMapGemParamter(['game_map_id' => $parentMap->id, 'name' => 'Fiery']);
        $mapGem = $this->createMapGeneratedGem($mapGemParamter, ['enemy_strength_increase' => 0.4]);
        $mapGemParamter->update(['rolled_gem_id' => $mapGem->id]);
        $generatedMap = $this->createGameMap([
            'name' => 'Fiery Map Gem World',
            'generated_map_type' => GeneratedGemMapType::MAP_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_map_gem_paramter_id' => $mapGemParamter->id,
            'can_traverse' => false,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $generatedMap)->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/map/gem-world/'.$character->id);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertTrue($data['inside_gem_world']);
        $this->assertNull($data['entry']);
        $this->assertSame('map_gem_world', $data['current_context']['type']);
        $this->assertSame($parentMap->id, $data['exit']['game_map']['id']);
        $this->assertSame($parentMap->name, $data['exit']['game_map']['name']);
        $this->assertSame('Exit Gem World', $data['exit']['label']);
    }

    public function test_context_inside_a_generated_location_gem_world_exposes_no_entry_and_exact_exit(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);
        $location = $this->createLocation(['game_map_id' => $parentMap->id, 'x' => 50, 'y' => 50]);
        $locationGemParamter = $this->createGameLocationGemParamter(['location_id' => $location->id, 'name' => 'Watery']);
        $locationGem = $this->createLocationGeneratedGem($locationGemParamter, ['enemy_strength_increase' => 0.5]);
        $locationGemParamter->update(['rolled_gem_id' => $locationGem->id]);
        $generatedLocationMap = $this->createGameMap([
            'name' => 'Watery Location Gem World',
            'generated_map_type' => GeneratedGemMapType::LOCATION_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_location_gem_paramter_id' => $locationGemParamter->id,
            'can_traverse' => false,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $generatedLocationMap)->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/map/gem-world/'.$character->id);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertTrue($data['inside_gem_world']);
        $this->assertNull($data['entry']);
        $this->assertSame('location_gem_world', $data['current_context']['type']);
        $this->assertSame($parentMap->id, $data['exit']['game_map']['id']);
    }

    public function test_entry_travels_the_character_to_the_generated_map_gem_world(): void
    {
        $this->instance(
            MapTileValue::class,
            Mockery::mock(MapTileValue::class, function (MockInterface $mock): void {
                $mock->shouldReceive('setUp')->andReturnSelf();
                $mock->shouldReceive('canWalk')->andReturn(true);
            })
        );

        $parentMap = $this->createGameMap(['name' => 'Surface']);
        $mapGemParamter = $this->createGameMapGemParamter(['game_map_id' => $parentMap->id, 'name' => 'Fiery']);
        $mapGem = $this->createMapGeneratedGem($mapGemParamter);
        $mapGemParamter->update(['rolled_gem_id' => $mapGem->id]);
        $generatedMap = $this->createGameMap([
            'name' => 'Fiery Map Gem World',
            'generated_map_type' => GeneratedGemMapType::MAP_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_map_gem_paramter_id' => $mapGemParamter->id,
            'can_traverse' => false,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $parentMap)->getCharacter();

        $response = $this->actingAs($character->user)
            ->postJson('/api/map/gem-world/enter/'.$character->id);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertSame('You entered the Gem World.', $data['message']);
        $this->assertSame($generatedMap->id, $character->refresh()->map->game_map_id);
    }

    public function test_entry_travels_the_character_to_the_generated_location_gem_world_not_the_map_gem_world(): void
    {
        $this->instance(
            MapTileValue::class,
            Mockery::mock(MapTileValue::class, function (MockInterface $mock): void {
                $mock->shouldReceive('setUp')->andReturnSelf();
                $mock->shouldReceive('canWalk')->andReturn(true);
            })
        );

        $parentMap = $this->createGameMap(['name' => 'Surface']);
        $mapGemParamter = $this->createGameMapGemParamter(['game_map_id' => $parentMap->id, 'name' => 'Fiery']);
        $mapGem = $this->createMapGeneratedGem($mapGemParamter);
        $mapGemParamter->update(['rolled_gem_id' => $mapGem->id]);
        $this->createGameMap([
            'name' => 'Fiery Map Gem World',
            'generated_map_type' => GeneratedGemMapType::MAP_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_map_gem_paramter_id' => $mapGemParamter->id,
            'can_traverse' => false,
        ]);

        $location = $this->createLocation(['game_map_id' => $parentMap->id, 'x' => 50, 'y' => 50]);
        $locationGemParamter = $this->createGameLocationGemParamter(['location_id' => $location->id, 'name' => 'Watery']);
        $locationGem = $this->createLocationGeneratedGem($locationGemParamter);
        $locationGemParamter->update(['rolled_gem_id' => $locationGem->id]);
        $generatedLocationMap = $this->createGameMap([
            'name' => 'Watery Location Gem World',
            'generated_map_type' => GeneratedGemMapType::LOCATION_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_location_gem_paramter_id' => $locationGemParamter->id,
            'can_traverse' => false,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(50, 50, $parentMap)->getCharacter();

        $response = $this->actingAs($character->user)
            ->postJson('/api/map/gem-world/enter/'.$character->id);

        $response->assertOk();
        $this->assertSame($generatedLocationMap->id, $character->refresh()->map->game_map_id);
    }

    public function test_entry_is_rejected_when_standing_on_a_location_with_no_eligible_location_gem(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);
        $mapGemParamter = $this->createGameMapGemParamter(['game_map_id' => $parentMap->id, 'name' => 'Fiery']);
        $mapGem = $this->createMapGeneratedGem($mapGemParamter);
        $mapGemParamter->update(['rolled_gem_id' => $mapGem->id]);
        $this->createGameMap([
            'name' => 'Fiery Map Gem World',
            'generated_map_type' => GeneratedGemMapType::MAP_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_map_gem_paramter_id' => $mapGemParamter->id,
            'can_traverse' => false,
        ]);

        $this->createLocation(['game_map_id' => $parentMap->id, 'x' => 60, 'y' => 60]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(60, 60, $parentMap)->getCharacter();

        $response = $this->actingAs($character->user)
            ->postJson('/api/map/gem-world/enter/'.$character->id);

        $data = json_decode($response->getContent(), true);

        $response->assertStatus(422);
        $this->assertSame('There is no Gem World available from your current Map or Location.', $data['message']);
        $this->assertSame($parentMap->id, $character->refresh()->map->game_map_id);
    }

    public function test_entry_is_rejected_when_character_is_already_inside_a_generated_gem_world(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);
        $mapGemParamter = $this->createGameMapGemParamter(['game_map_id' => $parentMap->id, 'name' => 'Fiery']);
        $mapGem = $this->createMapGeneratedGem($mapGemParamter);
        $mapGemParamter->update(['rolled_gem_id' => $mapGem->id]);
        $generatedMap = $this->createGameMap([
            'name' => 'Fiery Map Gem World',
            'generated_map_type' => GeneratedGemMapType::MAP_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_map_gem_paramter_id' => $mapGemParamter->id,
            'can_traverse' => false,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $generatedMap)->getCharacter();

        $response = $this->actingAs($character->user)
            ->postJson('/api/map/gem-world/enter/'.$character->id);

        $data = json_decode($response->getContent(), true);

        $response->assertStatus(422);
        $this->assertSame('You are already inside a Gem World.', $data['message']);
    }

    public function test_exit_travels_the_character_to_the_exact_generated_parent_map(): void
    {
        $this->instance(
            MapTileValue::class,
            Mockery::mock(MapTileValue::class, function (MockInterface $mock): void {
                $mock->shouldReceive('setUp')->andReturnSelf();
                $mock->shouldReceive('canWalk')->andReturn(true);
            })
        );

        $parentMap = $this->createGameMap(['name' => 'Surface']);
        $mapGemParamter = $this->createGameMapGemParamter(['game_map_id' => $parentMap->id, 'name' => 'Fiery']);
        $mapGem = $this->createMapGeneratedGem($mapGemParamter);
        $mapGemParamter->update(['rolled_gem_id' => $mapGem->id]);
        $generatedMap = $this->createGameMap([
            'name' => 'Fiery Map Gem World',
            'generated_map_type' => GeneratedGemMapType::MAP_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_map_gem_paramter_id' => $mapGemParamter->id,
            'can_traverse' => false,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $generatedMap)->getCharacter();

        $response = $this->actingAs($character->user)
            ->postJson('/api/map/gem-world/exit/'.$character->id);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertSame('You exited the Gem World.', $data['message']);
        $this->assertSame($parentMap->id, $character->refresh()->map->game_map_id);
    }

    public function test_exit_is_rejected_when_the_character_is_not_inside_a_gem_world(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $parentMap)->getCharacter();

        $response = $this->actingAs($character->user)
            ->postJson('/api/map/gem-world/exit/'.$character->id);

        $data = json_decode($response->getContent(), true);

        $response->assertStatus(422);
        $this->assertSame('You are not inside a Gem World.', $data['message']);
    }

    public function test_gem_world_context_endpoint_rejects_a_request_for_another_characters_id(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $parentMap)->getCharacter();
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $parentMap)->getCharacter();

        $response = $this->actingAs($character->user)
            ->getJson('/api/map/gem-world/'.$otherCharacter->id);

        $response->assertStatus(422);
        $this->assertSame('You don\'t have permission to do that.', json_decode($response->getContent(), true)['error']);
    }

    public function test_entering_a_map_gem_world_returns_the_cached_gem_affected_monster_and_effective_stats(): void
    {
        $this->instance(
            MapTileValue::class,
            Mockery::mock(MapTileValue::class, function (MockInterface $mock): void {
                $mock->shouldReceive('setUp')->andReturnSelf();
                $mock->shouldReceive('canWalk')->andReturn(true);
            })
        );

        $parentMap = $this->createGameMap(['name' => 'Surface']);
        $monster = $this->createMonster(['game_map_id' => $parentMap->id, 'str' => 10]);

        $mapGemParamter = $this->createGameMapGemParamter(['game_map_id' => $parentMap->id, 'name' => 'Fiery']);
        $mapGem = $this->createMapGeneratedGem($mapGemParamter, ['enemy_strength_increase' => 0.5]);
        $mapGemParamter->update(['rolled_gem_id' => $mapGem->id]);
        $generatedMap = $this->createGameMap([
            'name' => 'Fiery Map Gem World',
            'generated_map_type' => GeneratedGemMapType::MAP_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_map_gem_paramter_id' => $mapGemParamter->id,
            'can_traverse' => false,
        ]);

        resolve(BuildMonsterCacheService::class)->buildAll();

        $cachedMonster = collect(Cache::get(MonsterCacheKey::MONSTERS->value)[$generatedMap->name]['data'])
            ->firstWhere('id', $monster->id);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $parentMap)->getCharacter();

        $enterResponse = $this->actingAs($character->user)
            ->postJson('/api/map/gem-world/enter/'.$character->id);

        $enterResponse->assertOk();

        $listResponse = $this->actingAs($character->user)
            ->call('GET', '/api/monster-list/'.$character->id);

        $listData = json_decode($listResponse->getContent(), true);

        $this->assertNotEmpty(collect($listData)->where('id', $monster->id));

        $statsResponse = $this->actingAs($character->user)
            ->call('GET', '/api/monster-stat/'.$monster->id.'/'.$character->id);

        $statsData = json_decode($statsResponse->getContent(), true);

        $preview = $statsData['gem_effect_context_preview'];
        $strChange = collect($preview['changed_values'])->firstWhere('field', 'str');

        $this->assertSame('map_gem_world', $preview['type']);
        $this->assertNotNull($strChange);
        $this->assertSame($cachedMonster['str'], $strChange['effective_value']);
    }

    public function test_entering_a_location_gem_world_returns_the_cached_gem_affected_monster_and_effective_stats(): void
    {
        $this->instance(
            MapTileValue::class,
            Mockery::mock(MapTileValue::class, function (MockInterface $mock): void {
                $mock->shouldReceive('setUp')->andReturnSelf();
                $mock->shouldReceive('canWalk')->andReturn(true);
            })
        );

        $parentMap = $this->createGameMap(['name' => 'Surface']);
        $monster = $this->createMonster(['game_map_id' => $parentMap->id, 'str' => 10]);

        $location = $this->createLocation(['game_map_id' => $parentMap->id, 'x' => 50, 'y' => 50]);
        $locationGemParamter = $this->createGameLocationGemParamter(['location_id' => $location->id, 'name' => 'Watery']);
        $locationGem = $this->createLocationGeneratedGem($locationGemParamter, ['enemy_strength_increase' => 0.6]);
        $locationGemParamter->update(['rolled_gem_id' => $locationGem->id]);
        $generatedLocationMap = $this->createGameMap([
            'name' => 'Watery Location Gem World',
            'generated_map_type' => GeneratedGemMapType::LOCATION_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_location_gem_paramter_id' => $locationGemParamter->id,
            'can_traverse' => false,
        ]);

        resolve(BuildMonsterCacheService::class)->buildAll();

        $cachedMonster = collect(Cache::get(MonsterCacheKey::MONSTERS->value)[$generatedLocationMap->name]['data'])
            ->firstWhere('id', $monster->id);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(50, 50, $parentMap)->getCharacter();

        $enterResponse = $this->actingAs($character->user)
            ->postJson('/api/map/gem-world/enter/'.$character->id);

        $enterResponse->assertOk();
        $this->assertSame($generatedLocationMap->id, $character->refresh()->map->game_map_id);

        $statsResponse = $this->actingAs($character->user)
            ->call('GET', '/api/monster-stat/'.$monster->id.'/'.$character->id);

        $statsData = json_decode($statsResponse->getContent(), true);

        $preview = $statsData['gem_effect_context_preview'];
        $strChange = collect($preview['changed_values'])->firstWhere('field', 'str');

        $this->assertSame('location_gem_world', $preview['type']);
        $this->assertNotNull($strChange);
        $this->assertSame($cachedMonster['str'], $strChange['effective_value']);
    }
}
