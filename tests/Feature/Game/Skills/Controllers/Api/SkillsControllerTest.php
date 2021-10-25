<?php

namespace Tests\Feature\Game\Skills\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Skill;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use Tests\Setup\Character\CharacterFactory;

class SkillsControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateItem,
        CreateUser;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testCanTrainSkill() {
        $user      = $this->character->getUser();
        $character = $this->character->getCharacter();

        $this->actingAs($user)->json('post', '/api/skill/train/' . $character->id, [
            'skill_id'      => $this->fetchSkill('Looting')->id,
            'xp_percentage' => 0.10,
        ]);

        $character = $character->refresh();

        $skillTraining = $character->skills->filter(function($skill) {
            return $skill->currently_training;
        })->first();

        $this->assertNotNull($skillTraining);
        $this->assertEquals(0.10, $skillTraining->xp_towards);
    }

    public function testCannotTrainSkillInvalidSkillId() {
        $user      = $this->character->getUser();
        $character = $this->character->getCharacter();

        $response = $this->actingAs($user)->json('post', '/api/skill/train/' . $character->id, [
            'skill_id'      => 4768,
            'xp_percentage' => 0.10,
        ])->response;

        $this->assertEquals(422, $response->status());

        $character = $character->refresh();

        $skillTraining = $character->skills->filter(function($skill) {
            return $skill->currently_training;
        })->first();

        $this->assertNull($skillTraining);
    }

    public function testCannotTrainSkillInvalidInput() {
        $user      = $this->character->getUser();
        $character = $this->character->getCharacter();

        $response = $this->actingAs($user)->json('post', '/api/skill/train/' . $character->id, [
            'skill_id'      => $this->fetchSkill('Looting')->id,
            'xp_percentage' => 2000,
        ])->response;

        $this->assertEquals(422, $response->status());

        $character = $character->refresh();

        $skillTraining = $character->skills->filter(function($skill) {
            return $skill->currently_training;
        })->first();

        $this->assertNull($skillTraining);
    }

    public function testCanTrainDifferentSkill() {
        $user      = $this->character->getUser();
        $character = $this->character->trainSkill('Dodge')->getCharacter();

        $this->actingAs($user)->json('post', '/api/skill/train/' . $character->id, [
            'skill_id'      => $this->fetchSkill('Looting')->id,
            'xp_percentage' => 0.10,
        ]);

        $character = $this->character->getCharacter();

        $this->assertFalse($this->fetchSkill('Dodge', $character)->currently_training);
        $this->assertTrue($this->fetchSkill('Looting', $character)->currently_training);
    }

    public function testCanChangeXP() {

        $user      = $this->character->getUser();
        $character = $this->character->trainSkill('Dodge')
            ->updateSkill('Dodge', [
                'xp_towards' => 1
            ])
            ->getCharacter();

        $this->actingAs($user)->json('post', '/api/skill/train/' . $character->id, [
            'skill_id'      => $this->fetchSkill('Dodge')->id,
            'xp_percentage' => 0.10,
        ]);

        $this->assertEquals($this->fetchSkill('Dodge')->xp_towards, 0.10);
    }

    public function testCanCancelTrain() {
        $user      = $this->character->trainSkill('Dodge')
            ->updateSkill('Dodge', [
                'xp_towards' => 1
            ])
            ->getUser();

        $this->actingAs($user)->json('post', '/api/skill/cancel-train/'.$user->character->id.'/' . $this->fetchSkill('Dodge')->id);

        $this->assertEquals($this->fetchSkill('Dodge')->xp_towards, 0.0);
        $this->assertFalse($this->fetchSkill('Dodge')->currently_training);
    }

    protected function fetchSkill(string $name): Skill {
        return $this->character->getCharacter()->skills->where('name', $name)->first();
    }
}
