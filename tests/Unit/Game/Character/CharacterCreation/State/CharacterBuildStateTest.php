<?php

namespace Tests\Unit\Game\Character\CharacterCreation\State;

use App\Flare\Models\Character;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameRace;
use App\Flare\Models\User;
use App\Flare\Values\MapNameValue;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateUser;

class CharacterBuildStateTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRace,
        CreateClass,
        CreateCharacter,
        CreateGameMap;

    public function testDefaultsAreNull(): void
    {
        $state = app(CharacterBuildState::class);

        $this->assertNull($state->getUser());
        $this->assertNull($state->getRace());
        $this->assertNull($state->getClass());
        $this->assertNull($state->getMap());
        $this->assertNull($state->getCharacter());
        $this->assertNull($state->getNow());
    }

    public function testSettersStoreInstancesAndAreFluent(): void
    {
        $state = app(CharacterBuildState::class);

        $user = $this->createUser();
        $race = $this->createRace();
        $class = $this->createClass();

        $map = $this->createGameMap([
            'name' => MapNameValue::SURFACE,
        ]);

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

        $now = now();

        $returned = $state
            ->setUser($user)
            ->setRace($race)
            ->setClass($class)
            ->setMap($map)
            ->setCharacter($character)
            ->setNow($now);

        $this->assertSame($state, $returned);

        $this->assertInstanceOf(User::class, $state->getUser());
        $this->assertInstanceOf(GameRace::class, $state->getRace());
        $this->assertInstanceOf(GameClass::class, $state->getClass());
        $this->assertInstanceOf(GameMap::class, $state->getMap());
        $this->assertInstanceOf(Character::class, $state->getCharacter());
        $this->assertInstanceOf(DateTimeInterface::class, $state->getNow());

        $this->assertSame($user->id, $state->getUser()->id);
        $this->assertSame($race->id, $state->getRace()->id);
        $this->assertSame($class->id, $state->getClass()->id);
        $this->assertSame($map->id, $state->getMap()->id);
        $this->assertSame($character->id, $state->getCharacter()->id);
        $this->assertSame($now, $state->getNow());
    }

    public function testIndividualSettersAndGetters(): void
    {
        $state = app(CharacterBuildState::class);

        $user = $this->createUser();
        $this->assertSame($state, $state->setUser($user));
        $this->assertSame($user->id, $state->getUser()->id);

        $race = $this->createRace();
        $this->assertSame($state, $state->setRace($race));
        $this->assertSame($race->id, $state->getRace()->id);

        $class = $this->createClass();
        $this->assertSame($state, $state->setClass($class));
        $this->assertSame($class->id, $state->getClass()->id);

        $map = $this->createGameMap(['name' => MapNameValue::SURFACE]);
        $this->assertSame($state, $state->setMap($map));
        $this->assertSame($map->id, $state->getMap()->id);

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

        $this->assertSame($state, $state->setCharacter($character));
        $this->assertSame($character->id, $state->getCharacter()->id);

        $now = now();
        $this->assertSame($state, $state->setNow($now));
        $this->assertSame($now, $state->getNow());
    }
}
