<?php

namespace Tests\Unit\Flare;

use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGameMap;
use App\Flare\Builders\CharacterBuilder;
use App\Flare\Models\Character;
use App\Flare\Models\Skill;

class CreateCharacterTest extends TestCase
{

    use RefreshDatabase,
        CreateRace,
        CreateClass,
        CreateItem,
        CreateUser,
        CreateGameSkill,
        CreateGameMap;

    private $gameMap;

    public function setUp(): void {
        parent::setup();

        $this->createGameSkill([
            'name' => 'Accuracy'
        ]);

        $this->createGameSkill([
            'name' => 'Dodge'
        ]);

        $this->createItem([
            'name' => 'Rusty Dagger',
            'type' => 'weapon',
        ]);

        $this->gameMap = $this->createGameMap([
            'name'    => 'surface',
            'path'    => 'some-path',
            'default' => true,
        ]);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->gameMap = null;
    }

    public function testCreateCharacter()
    {
        $race = $this->createRace([
            'str_mod' => 3,
        ]);

        $class = $this->createClass([
            'dex_mod'     => 3,
            'damage_stat' => 'dex',
        ]);

        $character = resolve(CharacterBuilder::class)->setRace($race)
                                                     ->setClass($class)
                                                     ->createCharacter($this->createUser(), $this->gameMap, 'sample')
                                                     ->assignSkills()
                                                     ->character();

        $this->assertEquals('sample', $character->name);
        $this->assertEquals(13.0, $character->str);
        $this->assertEquals(13, $character->dex);
        $this->assertEquals('dex', $character->damage_stat);
        $this->assertEquals($race->name, $character->race->name);
        $this->assertEquals($class->name, $character->class->name);
        $this->assertFalse($character->inventory->slots->isEmpty());
    }

    public function testCreateCharacterWithSkills() {
        $race = $this->createRace([
            'str_mod' => 3,
            'accuracy_mod' => 2,
            'dodge_mod' => 2
        ]);

        $class = $this->createClass([
            'dex_mod'     => 3,
            'damage_stat' => 'dex',
            'accuracy_mod' => 0,
            'dodge_mod' => 1,
        ]);

        $character = resolve(CharacterBuilder::class)->setRace($race)
                                                     ->setClass($class)
                                                     ->createCharacter($this->createUser(), $this->gameMap, 'sample')
                                                     ->assignSkills()
                                                     ->character();


        $this->assertGreaterThan(0, number_format($this->fetchSkill('Accuracy', $character)->skill_bonus));
        $this->assertGreaterThan(0, number_format($this->fetchSkill('Dodge', $character)->skill_bonus));
    }

    public function testCreateCharacterWithSkillsAndClassSkills() {
        $race = $this->createRace([
            'str_mod' => 3,
            'accuracy_mod' => 2,
            'dodge_mod' => 2
        ]);

        $class = $this->createClass([
            'dex_mod'     => 3,
            'damage_stat' => 'dex',
            'accuracy_mod' => 0,
            'dodge_mod' => 1,
        ]);

        $gameSkill = $this->createGameSkill([
            'description'                        => 'Sample',
            'name'                               => 'Sample',
            'max_level'                          => 5,
            'base_damage_mod_bonus_per_level'    => 0,
            'base_healing_mod_bonus_per_level'   => 0,
            'base_ac_mod_bonus_per_level'        => 0,
            'fight_time_out_mod_bonus_per_level' => 0,
            'move_time_out_mod_bonus_per_level'  => 0,
            'game_class_id'                      => null,
            'can_train'                          => true,
            'skill_bonus_per_level'              => 0.01,
            'type'                               => SkillTypeValue::TRAINING,
            'game_class_id'                      => $class->id,
        ]);

        $character = resolve(CharacterBuilder::class)->setRace($race)
            ->setClass($class)
            ->createCharacter($this->createUser(), $this->gameMap, 'sample')
            ->assignSkills()
            ->character();

        $this->assertNotNull($character->refresh()->skills()->where('game_skill_id', $gameSkill->id));
    }

    public function testCreateTestCharacterWithOutUser() {
        $race = $this->createRace([
            'str_mod' => 3,
            'accuracy_mod' => 2,
            'dodge_mod' => 2
        ]);

        $class = $this->createClass([
            'dex_mod'     => 3,
            'damage_stat' => 'dex',
            'accuracy_mod' => 0,
            'dodge_mod' => 1,
        ]);


        $character = resolve(CharacterBuilder::class)->setRace($race)
            ->setClass($class)
            ->createTestCharacter($this->gameMap, 'sample')
            ->assignSkills()
            ->character();

        $this->assertGreaterThan(0, number_format($this->fetchSkill('Accuracy', $character)->skill_bonus));
        $this->assertGreaterThan(0, number_format($this->fetchSkill('Dodge', $character)->skill_bonus));
        $this->assertNull($character->user);
    }

    protected function fetchSkill(string $name, Character $character): Skill {
        return $character->skills()->join('game_skills', function($join) use($name){
            $join->on('game_skills.id', 'skills.game_skill_id')
                 ->where('name', $name);
        })->first();
    }
}
