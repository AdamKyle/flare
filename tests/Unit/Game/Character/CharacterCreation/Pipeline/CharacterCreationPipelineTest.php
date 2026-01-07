<?php

namespace Tests\Unit\Game\Character\CharacterCreation\Pipeline;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterClassRank;
use App\Flare\Models\CharacterClassRankWeaponMastery;
use App\Flare\Models\CharacterPassiveSkill;
use App\Flare\Models\GameClass;
use App\Flare\Models\Skill;
use App\Flare\Values\MapNameValue;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use App\Game\Character\CharacterCreation\Pipeline\CharacterCreationPipeline;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreatePassiveSkill;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateUser;

class CharacterCreationPipelineTest extends TestCase
{
    use CreateCharacter,
        CreateClass,
        CreateGameMap,
        CreateGameSkill,
        CreateItem,
        CreatePassiveSkill,
        CreateRace,
        CreateUser,
        RefreshDatabase;

    public function test_run_builds_character_and_runs_cache_builder(): void
    {
        $fighter = $this->createClass(['name' => 'Fighter', 'damage_stat' => 'str']);
        $this->createClass(['name' => 'Mage']);

        $surface = $this->seedPipelinePrerequisites($fighter);

        $user = $this->createUser();
        $race = $this->createRace();

        $this->bindBuildCharacterAttackTypesMock(true);

        $state = app(CharacterBuildState::class)
            ->setUser($user)
            ->setRace($race)
            ->setClass($fighter)
            ->setMap($surface)
            ->setCharacterName('PipelineUser')
            ->setNow(now());

        $final = app(CharacterCreationPipeline::class)->run($state);

        $this->assertInstanceOf(CharacterBuildState::class, $final);
        $this->assertInstanceOf(Character::class, $final->getCharacter());

        $character = Character::query()->find($final->getCharacter()->id);

        $this->assertNotNull($character);
        $this->assertSame('PipelineUser', $character->name);
    }

    public function test_run_no_op_with_empty_state(): void
    {
        $this->bindBuildCharacterAttackTypesMock(false);

        $state = app(CharacterBuildState::class)->setNow(now());

        $final = app(CharacterCreationPipeline::class)->run($state);

        $this->assertInstanceOf(CharacterBuildState::class, $final);
        $this->assertNull($final->getCharacter());
        $this->assertSame(0, Character::query()->count());
        $this->assertSame(0, Skill::query()->count());
        $this->assertSame(0, CharacterPassiveSkill::query()->count());
        $this->assertSame(0, CharacterClassRank::query()->count());
        $this->assertSame(0, CharacterClassRankWeaponMastery::query()->count());
    }

    private function seedPipelinePrerequisites(GameClass $fighter): mixed
    {
        $surface = $this->createGameMap(['name' => MapNameValue::SURFACE]);
        $this->createGameMap(['name' => MapNameValue::HELL]);
        $this->createGameMap(['name' => MapNameValue::PURGATORY]);

        $this->createItem([
            'name' => 'Rusty blade',
            'type' => 'sword',
            'base_damage' => 3,
            'skill_level_required' => 1,
        ]);

        $this->createGameSkill(['name' => 'General A', 'game_class_id' => null]);
        $this->createGameSkill(['name' => 'Fighter Skill', 'game_class_id' => $fighter->id]);

        $parentPassive = $this->createPassiveSkill([
            'is_locked' => false,
            'unlocks_at_level' => 0,
            'parent_skill_id' => null,
        ]);

        $this->createPassiveSkill([
            'parent_skill_id' => $parentPassive->id,
            'unlocks_at_level' => 1,
        ]);

        return $surface;
    }

    private function bindBuildCharacterAttackTypesMock(bool $expectCalled): void
    {
        $mock = Mockery::mock(BuildCharacterAttackTypes::class);

        if ($expectCalled) {
            $mock->shouldReceive('buildCache')->once()->with(Mockery::type(Character::class));
        } else {
            $mock->shouldReceive('buildCache')->never();
        }

        $this->app->instance(BuildCharacterAttackTypes::class, $mock);
    }
}
