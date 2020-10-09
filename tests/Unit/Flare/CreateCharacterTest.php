<?php

namespace Tests\Unit\Flare;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use App\Flare\Models\GameMap;
use App\Flare\Builders\CharacterBuilder;
use App\Flare\Models\Character;
use App\Flare\Models\Skill;
use Database\Seeders\GameSkillsSeeder;

class CreateCharacterTest extends TestCase
{

    use RefreshDatabase,
        CreateRace,
        CreateClass,
        CreateItem,
        CreateUser;

    private $gameMap;

    public function setUp(): void {
        parent::setup();

        $this->seed(GameSkillsSeeder::class);

        $this->createItem([
            'name' => 'Rusty Dagger',
            'type' => 'weapon',
        ]);

        $path = Storage::disk('maps')->putFile('Surface', resource_path('maps/surface.jpg'));

        $this->gameMap = GameMap::create([
            'name'    => 'surface',
            'path'    => $path,
            'default' => true,
        ]);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->gameMap = null;

        Storage::disk('maps')->deleteDirectory('Surface/');
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
        $this->assertEquals(4, $character->str);
        $this->assertEquals(4, $character->dex);
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
        

        $this->assertEquals('2.0', $this->fetchSkill('Accuracy', $character)->skill_bonus);
        $this->assertEquals('3.0', $this->fetchSkill('Dodge', $character)->skill_bonus);
    }

    protected function fetchSkill(string $name, Character $character): Skill {
        return $character->skills()->join('game_skills', function($join) use($name){
            $join->on('game_skills.id', 'skills.game_skill_id')
                 ->where('name', $name);
        })->first();
    }
}
