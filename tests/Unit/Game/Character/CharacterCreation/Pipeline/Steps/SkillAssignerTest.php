<?php

namespace Tests\Unit\Game\Character\CharacterCreation\Pipeline\Steps;

use App\Flare\Models\Skill;
use App\Game\Character\CharacterCreation\Pipeline\Steps\SkillAssigner;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateUser;

class SkillAssignerTest extends TestCase
{
    use CreateCharacter,
        CreateClass,
        CreateGameSkill,
        CreateRace,
        CreateUser,
        RefreshDatabase;

    public function test_assigns_general_and_class_skills(): void
    {
        $user = $this->createUser();
        $race = $this->createRace();
        $class = $this->createClass(['damage_stat' => 'str']);

        $generalA = $this->createGameSkill(['name' => 'General A', 'game_class_id' => null]);
        $generalB = $this->createGameSkill(['name' => 'General B', 'game_class_id' => null]);

        $classSkill = $this->createGameSkill(['name' => 'Class Skill', 'game_class_id' => $class->id]);

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

        $step = app(SkillAssigner::class);

        $result = $step->process($state, function (CharacterBuildState $s) {
            return $s;
        });

        $this->assertInstanceOf(CharacterBuildState::class, $result);

        $skillsForCharacter = Skill::query()->where('character_id', $character->id)->get();
        $this->assertSame(3, $skillsForCharacter->count());

        $skillIds = $skillsForCharacter->pluck('game_skill_id')->sort()->values()->all();
        $expected = collect([$generalA->id, $generalB->id, $classSkill->id])->sort()->values()->all();
        $this->assertSame($expected, $skillIds);
    }

    public function test_no_op_when_state_has_no_character(): void
    {
        $user = $this->createUser();
        $race = $this->createRace();
        $class = $this->createClass();

        $this->createGameSkill(['name' => 'General Only', 'game_class_id' => null]);

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

        $step = app(SkillAssigner::class);

        $result = $step->process($state, function (CharacterBuildState $s) {
            return $s;
        });

        $this->assertInstanceOf(CharacterBuildState::class, $result);

        $this->assertSame(0, Skill::query()->where('character_id', $character->id)->count());
    }
}
