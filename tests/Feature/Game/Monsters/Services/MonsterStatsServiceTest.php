<?php

namespace Tests\Feature\Game\Monsters\Services;

use App\Game\Gems\Values\GemTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMapGemParamter;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateMonster;

class MonsterStatsServiceTest extends TestCase
{
    use CreateGameMapGemParamter, CreateGem, CreateMonster, RefreshDatabase;

    public function test_monster_stats_return_the_effective_cached_atonement_used_by_the_fight(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $gameMap = $character->map->gameMap;

        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, [
            'monster_atonement' => GemTypeValue::FIRE,
            'monster_atonement_amount' => 0.3,
        ]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $monster = $this->createMonster([
            'game_map_id' => $gameMap->id,
            'fire_atonement' => 0,
            'ice_atonement' => 0,
            'water_atonement' => 0,
        ]);

        $response = $this->actingAs($character->user)
            ->getJson('/api/monster-stat/'.$monster->id.'/'.$character->id);

        $response->assertOk();
        $this->assertEquals(0.3, $response->json('raid_and_special.fire_atonement'));
        $this->assertEquals(0.0, $response->json('raid_and_special.ice_atonement'));
        $this->assertEquals(0.0, $response->json('raid_and_special.water_atonement'));
    }

    public function test_monster_stats_return_the_cached_elemental_penetration_used_by_the_fight(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'fire_atonement' => 0.5,
            'ice_atonement' => 0.2,
            'water_atonement' => 0.0,
            'fire_penetration' => 0.2,
            'ice_penetration' => 0.1,
            'water_penetration' => 0.0,
        ]);

        $response = $this->actingAs($character->user)
            ->getJson('/api/monster-stat/'.$monster->id.'/'.$character->id);

        $response->assertOk();
        $this->assertEquals(0.2, $response->json('raid_and_special.fire_penetration'));
        $this->assertEquals(0.1, $response->json('raid_and_special.ice_penetration'));
        $this->assertEquals(0.0, $response->json('raid_and_special.water_penetration'));
    }
}
