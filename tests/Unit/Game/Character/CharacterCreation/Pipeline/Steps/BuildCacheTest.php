<?php

namespace Tests\Unit\Game\Character\CharacterCreation\Pipeline\Steps;

use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use App\Game\Character\CharacterCreation\Pipeline\Steps\BuildCache;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateUser;

class BuildCacheTest extends TestCase
{
    use CreateCharacter,
        CreateClass,
        CreateRace,
        CreateUser,
        RefreshDatabase;

    public function test_builds_cache_when_character_present(): void
    {
        $user = $this->createUser();
        $race = $this->createRace();
        $class = $this->createClass();

        $character = $this->createCharacter([
            'damage_stat' => $class->damage_stat,
            'name' => Str::random(10),
            'user_id' => $user->id,
            'level' => 1,
            'xp' => 0,
            'can_attack' => true,
            'can_move' => true,
            'inventory_max' => 75,
            'gold' => 10,
            'game_class_id' => $class->id,
            'game_race_id' => $race->id,
        ]);

        $mock = Mockery::mock(BuildCharacterAttackTypes::class);
        $mock->shouldReceive('buildCache')->once()->with($character);
        $this->app->instance(BuildCharacterAttackTypes::class, $mock);

        $state = app(CharacterBuildState::class)
            ->setUser($user)
            ->setRace($race)
            ->setClass($class)
            ->setCharacter($character)
            ->setNow(now());

        $step = app(BuildCache::class);

        $result = $step->process($state, function (CharacterBuildState $s) {
            return $s;
        });

        $this->assertInstanceOf(CharacterBuildState::class, $result);
    }

    public function test_no_op_when_state_has_no_character(): void
    {
        $mock = Mockery::mock(BuildCharacterAttackTypes::class);
        $mock->shouldReceive('buildCache')->never();
        $this->app->instance(BuildCharacterAttackTypes::class, $mock);

        $state = app(CharacterBuildState::class)->setNow(now());

        $step = app(BuildCache::class);

        $result = $step->process($state, function (CharacterBuildState $s) {
            return $s;
        });

        $this->assertInstanceOf(CharacterBuildState::class, $result);
    }
}
