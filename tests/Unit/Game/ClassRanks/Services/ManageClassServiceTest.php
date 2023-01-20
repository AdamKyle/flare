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

    public function testCannotSwitchToClassThatIsLocked() {
        $heretic     = $this->createClass([
            'name' => 'Heretic',
        ]);

        $thief       = $this->createClass([
            'name' => 'Thief',
        ]);

        $prisonerClass = $this->createClass([
            'name'                            => 'Prisoner',
            'primary_required_class_id'       => $heretic->id,
            'secondary_required_class_id'     => $thief->id,
            'primary_required_class_level'    => 10,
            'secondary_required_class_level'  => 20,
        ]);

        $character = $this->character->addAdditionalClassRanks([$heretic->id, $thief->id, $prisonerClass->id])
                                     ->getCharacter();

        $response = $this->manageClassService->switchClass($character, $prisonerClass);

        $this->assertEquals(422, $response['status']);
        $this->assertEquals('This class is locked. You must level this classes required classes to the specified levels.', $response['message']);
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
