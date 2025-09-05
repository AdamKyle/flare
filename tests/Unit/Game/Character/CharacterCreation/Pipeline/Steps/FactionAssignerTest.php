<?php

namespace Tests\Unit\Game\Character\CharacterCreation\Pipeline\Steps;

use App\Flare\Models\Character;
use App\Flare\Values\MapNameValue;
use App\Game\Character\CharacterCreation\Pipeline\Steps\FactionAssigner;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use App\Game\Core\Values\FactionLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateUser;

class FactionAssignerTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRace,
        CreateClass,
        CreateCharacter,
        CreateGameMap;

    public function testCreatesFactionsForNonPurgatoryMaps(): void
    {
        $user = $this->createUser();
        $race = $this->createRace();
        $class = $this->createClass();

        $surface = $this->createGameMap(['name' => MapNameValue::SURFACE]);
        $hell = $this->createGameMap(['name' => MapNameValue::HELL]);
        $purgatory = $this->createGameMap(['name' => MapNameValue::PURGATORY]);

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

        $step = app(FactionAssigner::class);

        $result = $step->process($state, function (CharacterBuildState $s) {
            return $s;
        });

        $this->assertInstanceOf(CharacterBuildState::class, $result);

        $reloaded = Character::query()->with('factions')->find($character->id);
        $this->assertNotNull($reloaded);

        $this->assertSame(2, $reloaded->factions->count());

        $expectedMapIds = collect([$surface->id, $hell->id])->sort()->values();
        $actualMapIds = $reloaded->factions->pluck('game_map_id')->sort()->values();
        $this->assertSame($expectedMapIds->all(), $actualMapIds->all());

        $expectedPoints = FactionLevel::getPointsNeeded(0);
        $this->assertTrue($reloaded->factions->every(function ($faction) use ($expectedPoints) {
            return (int) $faction->points_needed === (int) $expectedPoints;
        }));

        $this->assertFalse($reloaded->factions->pluck('game_map_id')->contains($purgatory->id));
    }

    public function testNoOpWhenStateHasNoCharacter(): void
    {
        $user = $this->createUser();
        $race = $this->createRace();
        $class = $this->createClass();

        $this->createGameMap(['name' => MapNameValue::SURFACE]);
        $this->createGameMap(['name' => MapNameValue::HELL]);
        $this->createGameMap(['name' => MapNameValue::PURGATORY]);

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

        $state = app(CharacterBuildState::class)->setNow(now());

        $step = app(FactionAssigner::class);

        $result = $step->process($state, function (CharacterBuildState $s) {
            return $s;
        });

        $this->assertInstanceOf(CharacterBuildState::class, $result);

        $reloaded = Character::query()->with('factions')->find($character->id);
        $this->assertNotNull($reloaded);
        $this->assertSame(0, $reloaded->factions->count());
    }
}
