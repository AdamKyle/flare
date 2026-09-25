<?php

namespace Tests\Unit\Game\Battle\Services;

use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Battle\ServerFight\MonsterPlayerFight;
use App\Game\Battle\Services\CelestialFightService;
use App\Game\Battle\Values\CelestialConjureType;
use App\Game\BattleRewardProcessing\Jobs\BattleAttackHandler;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Maps\Contracts\CoordinatesQuery;
use App\Game\Maps\Values\MapTileValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCelestials;
use Tests\Traits\CreateMonster;

class CelestialFightServiceTest extends TestCase
{
    use CreateCelestials, CreateMonster, RefreshDatabase;

    public function test_fight_dispatches_battle_attack_handler_immediately_when_celestial_monster_is_killed(): void
    {
        Queue::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'shards' => 5,
        ]);

        $celestialFight = $this->createCelestialFight([
            'monster_id' => $monster->id,
            'character_id' => $character->id,
            'x_position' => $character->map->character_position_x,
            'y_position' => $character->map->character_position_y,
            'damaged_kingdom' => false,
            'stole_treasury' => false,
            'weakened_morale' => false,
            'conjured_at' => now(),
            'current_health' => 100,
            'max_health' => 100,
            'type' => CelestialConjureType::PRIVATE,
        ]);

        $characterInCelestialFight = $this->createCharacterInCelestialFight([
            'character_id' => $character->id,
            'celestial_fight_id' => $celestialFight->id,
            'character_max_health' => 50,
            'character_current_health' => 50,
        ]);

        $monsterPlayerFight = Mockery::mock(MonsterPlayerFight::class);
        $monsterPlayerFight->shouldReceive('setUpFight')->once()->andReturnSelf();
        $monsterPlayerFight->shouldReceive('fightMonster')->once()->with(true)->andReturn(true);
        $monsterPlayerFight->shouldReceive('getBattleMessages')->andReturn([]);

        $characterCacheData = Mockery::mock(CharacterCacheData::class);
        $characterCacheData->shouldReceive('deleteCharacterSheet')->once();

        $service = new CelestialFightService(
            Mockery::mock(BattleEventHandler::class),
            $characterCacheData,
            $monsterPlayerFight,
            Mockery::mock(MapTileValue::class),
            Mockery::mock(RandomNumberGenerator::class),
            Mockery::mock(CoordinatesQuery::class),
        );

        $service->fight($character, $celestialFight, $characterInCelestialFight, 'attack');

        Queue::assertPushed(BattleAttackHandler::class, function (BattleAttackHandler $job): bool {
            return $job->queue === 'battle_reward_processing'
                && $job->connection === 'battle_reward_processing'
                && is_null($job->delay);
        });
    }
}
