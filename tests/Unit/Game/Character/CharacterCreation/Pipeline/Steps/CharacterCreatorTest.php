<?php

namespace Tests\Unit\Game\Character\CharacterCreation\Pipeline\Steps;

use App\Flare\Models\Character;
use App\Game\Character\CharacterCreation\Pipeline\Steps\CharacterCreator;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateUser;

class CharacterCreatorTest extends TestCase
{
    use RefreshDatabase, CreateUser, CreateRace, CreateClass;

    public function testCreatesCharacterWithBaseStatsAndStoresOnState(): void
    {
        $state = app(CharacterBuildState::class);

        $user = $this->createUser();

        $race = $this->createRace([
            'str_mod' => 1,
            'dex_mod' => 0,
            'dur_mod' => 0,
            'chr_mod' => 0,
            'int_mod' => 0,
            'agi_mod' => 0,
            'focus_mod' => 0,
            'defense_mod' => 0.0,
        ]);

        $class = $this->createClass([
            'str_mod' => 2,
            'dex_mod' => 0,
            'dur_mod' => 0,
            'chr_mod' => 0,
            'int_mod' => 0,
            'agi_mod' => 0,
            'focus_mod' => 0,
            'defense_mod' => 0.0,
            'damage_stat' => 'str',
        ]);

        $characterName = 'BobTest';

        $state->setUser($user)
            ->setRace($race)
            ->setClass($class)
            ->setCharacterName($characterName)
            ->setNow(now());

        $step = app(CharacterCreator::class);

        $result = $step->process($state, function (CharacterBuildState $s) {
            return $s;
        });

        $this->assertInstanceOf(CharacterBuildState::class, $result);
        $this->assertInstanceOf(Character::class, $result->getCharacter());

        $character = $result->getCharacter();

        $reloaded = \App\Flare\Models\Character::query()->find($character->id);
        $this->assertNotNull($reloaded);
        $this->assertSame($characterName, $reloaded->name);
        $this->assertSame($user->id, $reloaded->user_id);
        $this->assertSame($race->id, $reloaded->game_race_id);
        $this->assertSame($class->id, $reloaded->game_class_id);
        $this->assertSame('str', $reloaded->damage_stat);
        $this->assertSame(0, $reloaded->xp);
        $this->assertSame(100, $reloaded->xp_next);
        $this->assertSame(1000, $reloaded->gold);

        $this->assertSame(13, $character->str);
        $this->assertSame(10, $character->dex);
        $this->assertSame(10, $character->dur);
        $this->assertSame(10, $character->chr);
        $this->assertSame(10, $character->int);
        $this->assertSame(10, $character->agi);
        $this->assertSame(10, $character->focus);
        $this->assertSame(10, $character->ac);
    }

    public function testNoOpWhenStateMissingData(): void
    {
        $state = app(CharacterBuildState::class);

        $step = app(CharacterCreator::class);

        $result = $step->process($state, function (CharacterBuildState $s) {
            return $s;
        });

        $this->assertNull($result->getCharacter());
        $this->assertSame(0, \App\Flare\Models\Character::query()->count());
    }
}
