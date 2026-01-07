<?php

namespace Tests\Unit\Game\Character\CharacterCreation\Pipeline\Steps;

use App\Flare\Models\CharacterPassiveSkill;
use App\Game\Character\CharacterCreation\Pipeline\Steps\PassiveSkillAssigner;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateClass;
use Tests\Traits\CreatePassiveSkill;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateUser;

class PassiveSkillAssignerTest extends TestCase
{
    use CreateCharacter,
        CreateClass,
        CreatePassiveSkill,
        CreateRace,
        CreateUser,
        RefreshDatabase;

    public function test_assigns_top_level_and_child_passives(): void
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

        $parent = $this->createPassiveSkill([
            'is_locked' => false,
            'unlocks_at_level' => 0,
            'parent_skill_id' => null,
        ]);

        $child = $this->createPassiveSkill([
            'parent_skill_id' => $parent->id,
            'unlocks_at_level' => 1,
        ]);

        $state = app(CharacterBuildState::class)
            ->setUser($user)
            ->setRace($race)
            ->setClass($class)
            ->setCharacter($character)
            ->setNow(now());

        $step = app(PassiveSkillAssigner::class);

        $result = $step->process($state, function (CharacterBuildState $characterState) {
            return $characterState;
        });

        $this->assertInstanceOf(CharacterBuildState::class, $result);

        $rows = CharacterPassiveSkill::query()
            ->where('character_id', $character->id)
            ->whereIn('passive_skill_id', [$parent->id, $child->id])
            ->get();

        $this->assertSame(2, $rows->count());

        $parentRow = $rows->firstWhere('passive_skill_id', $parent->id);
        $childRow = $rows->firstWhere('passive_skill_id', $child->id);

        $this->assertNotNull($parentRow);
        $this->assertNotNull($childRow);

        $this->assertFalse($parentRow->is_locked);
        $this->assertTrue($childRow->is_locked);

        $this->assertNull($parentRow->parent_skill_id);
        $this->assertSame($parentRow->id, $childRow->parent_skill_id);
    }

    public function test_no_op_when_state_has_no_character(): void
    {
        $this->createPassiveSkill();

        $state = app(CharacterBuildState::class)->setNow(now());

        $step = app(PassiveSkillAssigner::class);

        $result = $step->process($state, function (CharacterBuildState $s) {
            return $s;
        });

        $this->assertInstanceOf(CharacterBuildState::class, $result);
        $this->assertSame(0, CharacterPassiveSkill::query()->count());
    }
}
