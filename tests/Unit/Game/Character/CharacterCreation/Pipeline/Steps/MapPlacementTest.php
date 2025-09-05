<?php

namespace Tests\Unit\Game\Character\CharacterCreation\Pipeline\Steps;

use App\Flare\Models\Character;
use App\Flare\Values\MapNameValue;
use App\Game\Character\CharacterCreation\Pipeline\Steps\MapPlacement;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateUser;

class MapPlacementTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRace,
        CreateClass,
        CreateCharacter,
        CreateGameMap;

    public function testPlacesCharacterOnMap(): void
    {
        $user = $this->createUser();
        $race = $this->createRace();
        $class = $this->createClass();
        $map = $this->createGameMap(['name' => MapNameValue::SURFACE]);

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
            ->setMap($map)
            ->setNow(now());

        $step = app(MapPlacement::class);

        $result = $step->process($state, function (CharacterBuildState $s) {
            return $s;
        });

        $this->assertInstanceOf(CharacterBuildState::class, $result);

        $reloaded = Character::query()->with('map')->find($character->id);
        $this->assertNotNull($reloaded);
        $this->assertNotNull($reloaded->map);
        $this->assertSame($map->id, $reloaded->map->game_map_id);
    }

    public function testNoOpWhenStateHasNoCharacter(): void
    {
        $map = $this->createGameMap(['name' => MapNameValue::SURFACE]);

        $state = app(CharacterBuildState::class)
            ->setMap($map)
            ->setNow(now());

        $step = app(MapPlacement::class);

        $result = $step->process($state, function (CharacterBuildState $s) {
            return $s;
        });

        $this->assertInstanceOf(CharacterBuildState::class, $result);
        $this->assertSame(0, Character::query()->has('map')->count());
    }

    public function testNoOpWhenStateHasNoMap(): void
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

        $step = app(MapPlacement::class);

        $result = $step->process($state, function (CharacterBuildState $s) {
            return $s;
        });

        $this->assertInstanceOf(CharacterBuildState::class, $result);

        $reloaded = Character::query()->with('map')->find($character->id);
        $this->assertNotNull($reloaded);
        $this->assertNull($reloaded->map);
    }
}
