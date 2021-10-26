<?php

namespace Tests\Unit\Admin\Services;

use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Admin\Services\AssignSkillService;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;

class AssignSkillsServiceTest extends TestCase
{
    use RefreshDatabase, CreateGameSkill;

    private $character;

    public function setUp(): void {
        parent::setup();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testAssignNewSkill() {
        $newSkill = $this->createGameSkill([
            'name' => 'Example Skill'
        ]);

        resolve(AssignSkillService::class)->assignSkills();

        $character = $this->character->getCharacter(false);

        $skill = $character->skills->filter(function($skill) use ($newSkill) {
          return $skill->name === $newSkill->name;
        })->first();

        $this->assertNotNull($skill);
    }

    public function testAssignNewClassSkill() {
        $character = $this->character->getCharacter(false);

        $newSkill = $this->createGameSkill([
            'name'          => 'Example Skill',
            'game_class_id' => $character->class->id,
        ]);

        resolve(AssignSkillService::class)->assignSkills();

        $character = $character->refresh();

        $skill = $character->skills->filter(function($skill) use ($newSkill) {
            return $skill->name === $newSkill->name;
        })->first();

        $this->assertNotNull($skill);
    }

    public function testDoNotAssignSkill() {
        $newSkill = $this->createGameSkill([
            'name' => 'Example Skill'
        ]);

        $this->character->assignSkill($newSkill);

        resolve(AssignSkillService::class)->assignSkills();

        $character = $this->character->getCharacter(false);

        $count = $character->skills->filter(function($skill) use ($newSkill) {
            return $skill->name === $newSkill->name;
        })->count();

        $this->assertEquals(1, $count);
    }
}
