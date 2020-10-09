<?php

namespace Tests\Feature\Game\Core;

use App\Flare\Models\ItemAffix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use Tests\Setup\CharacterSetup;

class CharacterSkillControllerTest extends TestCase
{
    // use RefreshDatabase,
    //     CreateItem,
    //     CreateUser;

    // private $character;

    // public function setUp(): void {
    //     parent::setUp();

    //     $this->character = (new CharacterSetup())
    //                             ->setupCharacter($this->createUser())
    //                             ->setSkill('Looting')
    //                             ->setSkill('Dodge')
    //                             ->setSkill('Accuracy')
    //                             ->getCharacter();
    // }

    // public function tearDown(): void {
    //     parent::tearDown();

    //     $this->character = null;
    // }

    // public function testCanTrainSkill() {
    //     $response = $this->actingAs($this->character->user)->post(route('train.skill'), [
    //         'skill_id'      => $this->character->skills->where('name', 'Looting')->first()->id,
    //         'xp_percentage' => 0.10,
    //     ])->response;

    //     $response->assertSessionHas('success', 'You are now training Looting');
    // }

    // public function testCannotTrainSkillInvalidSkillId() {
    //     $response = $this->actingAs($this->character->user)->post(route('train.skill'), [
    //         'skill_id'      => 6,
    //         'xp_percentage' => 0.10,
    //     ])->response;

    //     $response->assertSessionHas('error', 'Invalid Input.');
    // }

    // public function testCannotTrainSkillInvalidInput() {
    //     $response = $this->actingAs($this->character->user)->visitRoute('game.character.sheet')->post(route('train.skill'), [
    //         'skill_id'      => 2,
    //         'xp_percentage' => 2000,
    //     ])->followRedirects()->response;

    //     $this->assertTrue(strpos('Invalid Inout.', $response->content()) !== -1);
    // }

    // public function testCanTrainDifferentSkill() {
    //     $this->character->skills->where('name', 'Dodge')->first()->update([
    //         'currently_training' => true,
    //     ]);

    //     $this->actingAs($this->character->user)->visitRoute('game.character.sheet')->post(route('train.skill'), [
    //         'skill_id'      => $this->character->skills->where('name', 'Looting')->first()->id,
    //         'xp_percentage' => 0.10,
    //     ]);
    //     $this->character->refresh();

    //     $this->assertFalse($this->character->skills->where('name', 'Dodge')->first()->currently_training);
    //     $this->assertTrue($this->character->skills->where('name', 'Looting')->first()->currently_training);
    // }

    // public function testCanChangeXP() {
    //     $this->character->skills->where('name', 'Dodge')->first()->update([
    //         'currently_training' => true,
    //         'xp_towards'         => 1,
    //     ]);

    //     $this->assertEquals($this->character->skills->where('name', 'Dodge')->first()->xp_towards, 1);

    //     $this->actingAs($this->character->user)->visitRoute('game.character.sheet')->post(route('train.skill'), [
    //         'skill_id'      => $this->character->skills->where('name', 'Dodge')->first()->id,
    //         'xp_percentage' => 0.10,
    //     ]);

    //     $this->character->refresh();

    //     $this->assertEquals($this->character->skills->where('name', 'Dodge')->first()->xp_towards, 0.1);
    // }

    // public function testCanCancelTrain() {
    //     $this->character->skills->where('name', 'Dodge')->first()->update([
    //         'currently_training' => true,
    //         'xp_towards'         => 1,
    //     ]);

    //     $this->assertEquals($this->character->skills->where('name', 'Dodge')->first()->xp_towards, 1);

    //     $this->actingAs($this->character->user)->visitRoute('game.character.sheet')->post(route('cancel.train.skill', [
    //         'skill' => $this->character->skills->where('name', 'Dodge')->first()->id,
    //     ]));

    //     $this->character->refresh();

    //     $this->assertEquals($this->character->skills->where('name', 'Dodge')->first()->xp_towards, 0.0);
    //     $this->assertFalse($this->character->skills->where('name', 'Dodge')->first()->currently_training);
    // }

    // public function testShouldSeeSkillPage() {
    //     $this->actingAs($this->character->user)->visit(route('skill.character.info', [
    //         'skill' => $this->character->skills->where('name', 'Dodge')->first()->id,
    //     ]))->see('Dodge');
    // }

    // public function testNotShouldSeeSkillPage() {
    //     $this->visit(route('skill.character.info', [
    //         'skill' => $this->character->skills->where('name', 'Dodge')->first()->id,
    //     ]))->dontSee('Dodge');
    // }
}
