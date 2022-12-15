<?php

namespace Tests\Unit\Game\ClassRanks\Services;

use App\Flare\Values\BaseSkillValue;
use App\Game\ClassRanks\Services\ManageClassService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameSkill;

class ManageClassServiceTest extends TestCase {

    use RefreshDatabase, CreateClass, CreateGameSkill;

    private ?CharacterFactory $character;

    private ?ManageClassService $manageClassService;

    public function setUp(): void {
        parent::setUp();

        $this->character          = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $this->manageClassService = resolve(ManageClassService::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character          = null;
        $this->manageClassService = null;
    }

    public function testSwitchCharacterClass() {
        $character = $this->character->getCharacter();
        $skill     = $this->createGameSkill(['name' => 'Class Skill', 'game_class_id' => $character->game_class_id]);

        $skillData              = (new BaseSkillValue())->getBaseCharacterSkillValue($character, $skill);
        $skillData['is_locked'] = false;

        $character->skills()->create($skillData);

        $gameClass = $this->createClass(['name' => 'Heretic']);
        $gameSkill = $this->createGameSkill(['name' => 'Heretic Skill', 'game_class_id' => $gameClass->id]);

        $response = $this->manageClassService->switchClass($character, $gameClass);

        $this->assertEquals(200, $response['status']);

        $character = $character->refresh();

        $this->assertNotNull($character->skills->where('name', $gameSkill->name)->first());
        $this->assertEquals($character->game_class_id, $gameClass->id);
    }

    public function testReactivateSkill() {
        $character = $this->character->getCharacter();
        $skill     = $this->createGameSkill(['name' => 'Class Skill', 'game_class_id' => $character->game_class_id]);

        $skillData              = (new BaseSkillValue())->getBaseCharacterSkillValue($character, $skill);
        $skillData['is_locked'] = false;
        $skillData['is_hidden'] = true;

        $character->skills()->create($skillData);

        $character = $character->refresh();

        $response = $this->manageClassService->switchClass($character, $character->class);

        $this->assertEquals(200, $response['status']);

        $character = $character->refresh();

        $this->assertFalse($character->skills->where('name', $skill->name)->first()->is_hidden);
    }
}
