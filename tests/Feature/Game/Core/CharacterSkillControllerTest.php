<?php

namespace Tests\Feature\Game\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Skill;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use Tests\Setup\Character\CharacterFactory;

class CharacterSkillControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateItem,
        CreateUser;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testCanTrainSkill() {
        $user      = $this->character->getUser();
        $character = $this->character->getCharacter();

        $response = $this->actingAs($user)->post(route('train.skill', [
            'character' => $character->id
        ]), [
            'skill_id'      => $this->fetchSkill('Looting')->id,
            'xp_percentage' => 0.10,
        ])->response;

        $response->assertSessionHas('success', 'You are now training Looting');
    }

    public function testCannotTrainSkillInvalidSkillId() {
        $user      = $this->character->getUser();
        $character = $this->character->getCharacter();

        $response = $this->actingAs($user)->post(route('train.skill', [
            'character' => $character->id
        ]), [
            'skill_id'      => 69702,
            'xp_percentage' => 0.10,
        ])->response;

        $response->assertSessionHas('error', 'Invalid Input.');
    }

    public function testCannotTrainSkillInvalidInput() {
        $user      = $this->character->getUser();
        $character = $this->character->getCharacter();

        $response = $this->actingAs($user)->visitRoute('game.character.sheet')->post(route('train.skill', [
            'character' => $character->id,
        ]), [
            'skill_id'      => 2,
            'xp_percentage' => 2000,
        ])->followRedirects()->response;

        $this->assertTrue(strpos('Invalid Inout.', $response->content()) !== -1);
    }

    public function testCanTrainDifferentSkill() {
        $user      = $this->character->getUser();
        $character = $this->character->trainSkill('Dodge')->getCharacter();

        $this->actingAs($user)->visitRoute('game.character.sheet')->post(route('train.skill', [
            'character' => $character->id,
        ]), [
            'skill_id'      => $this->fetchSkill('Looting', $character)->id,
            'xp_percentage' => 0.10,
        ])->response;

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

        $this->actingAs($user)->visitRoute('game.character.sheet')->post(route('train.skill', [
            'character' => $character->id
        ]), [
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

        $this->actingAs($user)->visitRoute('game.character.sheet')->post(route('cancel.train.skill', [
            'skill' => $this->fetchSkill('Dodge')->id,
        ]));

        $this->assertEquals($this->fetchSkill('Dodge')->xp_towards, 0.0);
        $this->assertFalse($this->fetchSkill('Dodge')->currently_training);
    }

    public function testShouldSeeSkillPage() {
        $user = $this->character->getUser();

        $this->actingAs($user)->visit(route('skill.character.info', [
            'skill' => $this->fetchSkill('Dodge')->id,
        ]))->see('Dodge');
    }

    public function testNotShouldSeeSkillPage() {
        $this->visit(route('skill.character.info', [
            'skill' => $this->fetchSkill('Dodge')->id,
        ]))->dontSee('Dodge');
    }

    protected function fetchSkill(string $name): Skill {
        return $this->character->getCharacter()->skills->where('name', $name)->first();
    }
}
