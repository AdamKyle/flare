<?php

namespace Tests\Feature\Info\Monsters;

use App\Game\Monsters\Services\BuildMonsterCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_public_monster_detail_exposes_cached_gem_effect_contexts(): void
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

        $this->assertNotEmpty($data['gem_effect_contexts']);
        $this->assertSame($gem->id, $data['gem_effect_contexts'][0]['sources'][0]['rolled_gem_id']);
    }
}
