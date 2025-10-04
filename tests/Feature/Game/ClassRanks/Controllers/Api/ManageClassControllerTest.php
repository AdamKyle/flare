<?php

namespace Tests\Feature\Game\ClassRanks\Controllers\Api;

use App\Flare\Values\BaseSkillValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameSkill;

class ManageClassControllerTest extends TestCase
{
    use CreateClass, CreateGameSkill, RefreshDatabase;

    private ?CharacterFactory $character = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->assignSkill(
            $this->createGameSkill([
                'class_bonus' => 0.01,
            ]), 5
        )->givePlayerLocation();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_switch_class()
    {
        $character = $this->character->getCharacter();
        $skill = $this->createGameSkill(['name' => 'Class Skill', 'game_class_id' => $character->game_class_id]);

        $skillData = (new BaseSkillValue)->getBaseCharacterSkillValue($character, $skill);
        $skillData['is_locked'] = false;

        $character->skills()->create($skillData);

        $gameClass = $this->createClass(['name' => 'Heretic']);

        $this->createGameSkill(['name' => 'Heretic Skill', 'game_class_id' => $gameClass->id]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/switch-classes/'.$character->id.'/'.$gameClass->id, [
                '_token' => csrf_token(),
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('You have switched to: '.$gameClass->name, $jsonData['message']);
        $this->assertCount(1, $jsonData['class_ranks']);
    }
}
