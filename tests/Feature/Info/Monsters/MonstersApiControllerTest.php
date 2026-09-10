<?php

namespace Tests\Feature\Info\Monsters;

use App\Game\Monsters\Services\BuildMonsterCacheService;
use App\Game\Monsters\Values\MonsterCacheKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameMapGemParamter;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateMonster;

class MonstersApiControllerTest extends TestCase
{
    use CreateGameMap, CreateGameMapGemParamter, CreateGem, CreateMonster, RefreshDatabase;

    public function test_guest_can_access_public_monster_detail(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Public Monster Map']);
        $monster = $this->createMonster(['name' => 'Public Monster', 'game_map_id' => $gameMap->id]);

        $response = $this->call('GET', "/api/information/monsters/{$monster->id}", [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(200);
        $data = json_decode($response->getContent(), true);

        $this->assertSame('Public Monster', $data['identity']['name']);
        $this->assertSame('Public Monster Map', $data['identity']['game_map']['name']);
    }

    public function test_public_monster_detail_exposes_the_compact_gem_effect_summary(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Public Gem Effect Map']);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_strength_increase' => 0.2]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $monster = $this->createMonster(['name' => 'Public Gem Monster', 'game_map_id' => $gameMap->id, 'str' => 10]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $response = $this->call('GET', "/api/information/monsters/{$monster->id}", [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(200);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(1, $data['gem_effect_context_count']);
        $this->assertSame($gem->id, $data['gem_effect_context_preview']['sources'][0]['rolled_gem_id']);
        $this->assertArrayNotHasKey('gem_effect_contexts', $data);
    }

    public function test_guest_can_access_the_paginated_gem_effect_contexts_route_with_cache_derived_data(): void
    {
        $monster = $this->createMonster();

        $rows = [];

        for ($locationId = 1; $locationId <= 11; $locationId++) {
            $rows[] = [
                'id' => $monster->id,
                'gem_effect_context' => [
                    'has_effects' => true,
                    'context_type' => 'location',
                    'context_label' => sprintf('Location %02d', $locationId),
                    'game_map' => null,
                    'location' => ['id' => $locationId, 'name' => sprintf('Location %02d', $locationId)],
                    'sources' => [],
                    'character_power_reduction' => 0.0,
                ],
            ];
        }

        Cache::put(MonsterCacheKey::LOCATION_MONSTERS->value, [
            'Paginated Locations' => ['data' => $rows],
        ]);

        $response = $this->call('GET', "/api/information/monsters/{$monster->id}/gem-effect-contexts", [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true);

        $response->assertStatus(200);
        $this->assertCount(10, $data['data']);
        $this->assertSame(10, $data['meta']['pagination']['per_page']);
        $this->assertSame(11, $data['meta']['pagination']['total']);
        $this->assertTrue($data['meta']['can_load_more']);
    }
}
