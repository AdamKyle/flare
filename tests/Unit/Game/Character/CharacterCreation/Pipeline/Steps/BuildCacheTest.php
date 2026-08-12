<?php

namespace Tests\Unit\Game\Character\CharacterCreation\Pipeline\Steps;

use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use App\Game\Character\CharacterCreation\Jobs\BuildCharacterCacheData;
use App\Game\Character\CharacterCreation\Pipeline\Steps\BuildCache;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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

        $state = app(CharacterBuildState::class)
            ->setUser($user)
            ->setRace($race)
            ->setClass($class)
            ->setCharacter($character)
            ->setNow(now());

        $character = $state->getCharacter();

        BuildCharacterCacheData::dispatch($character->id);

        $this->assertNotNull($character);

    }

    public function test_no_op_when_state_has_no_character(): void
    {
        BuildCharacterCacheData::dispatch(0);

        $this->assertNull(Cache::get('character-attack-data-0'));
    }

    public function test_process_does_not_build_the_attack_cache_when_given_a_null_character(): void
    {
        $buildCharacterAttackTypes = Mockery::mock(BuildCharacterAttackTypes::class);
        $buildCharacterAttackTypes->shouldNotReceive('buildCache');

        (new BuildCache($buildCharacterAttackTypes))->process(null);

        $this->addToAssertionCount(1);

        Mockery::close();
    }
}
