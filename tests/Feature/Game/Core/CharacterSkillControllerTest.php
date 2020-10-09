<?php

namespace Tests\Feature\Game\Core;

use App\Flare\Models\Character;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\Skill;
use Database\Seeders\GameSkillsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use Tests\Setup\CharacterSetup;

class CharacterSkillControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateItem,
        CreateUser;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $this->seed(GameSkillsSeeder::class);

        $this->character = (new CharacterSetup())
                                ->setupCharacter($this->createUser())
                                ->setSkill('Looting')
                                ->setSkill('Dodge')
                                ->setSkill('Accuracy')
                                ->getCharacter();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testCanTrainSkill() {
        $response = $this->actingAs($this->character->user)->post(route('train.skill', [
            'character' => $this->character->id
        ]), [
            'skill_id'      => $this->fetchSkill('Looting', $this->character)->id,
            'xp_percentage' => 0.10,
        ])->response;

        $response->assertSessionHas('success', 'You are now training Looting');
    }

    public function testCannotTrainSkillInvalidSkillId() {
        $response = $this->actingAs($this->character->user)->post(route('train.skill', [
            'character' => $this->character->id
        ]), [
            'skill_id'      => 6,
            'xp_percentage' => 0.10,
        ])->response;

        $response->assertSessionHas('error', 'Invalid Input.');
    }

    public function testCannotTrainSkillInvalidInput() {
        $response = $this->actingAs($this->character->user)->visitRoute('game.character.sheet')->post(route('train.skill', [
            'character' => $this->character->id,
        ]), [
            'skill_id'      => 2,
            'xp_percentage' => 2000,
        ])->followRedirects()->response;

        $this->assertTrue(strpos('Invalid Inout.', $response->content()) !== -1);
    }

    public function testCanTrainDifferentSkill() {

        $this->fetchSkill('Dodge', $this->character)->update([
            'currently_training' => true,
        ]);

        $character = $this->character->refresh();

        $response = $this->actingAs($character->user)->visitRoute('game.character.sheet')->post(route('train.skill', [
            'character' => $character->id,
        ]), [
            'skill_id'      => $this->fetchSkill('Looting', $character)->id,
            'xp_percentage' => 0.10,
        ])->response;

        
        $character = $this->character->refresh();

        $this->assertFalse($this->fetchSkill('Dodge', $character)->currently_training);
        $this->assertTrue($this->fetchSkill('Looting', $character)->currently_training);
    }

    public function testCanChangeXP() {
        $this->fetchSkill('Dodge', $this->character)->update([
            'currently_training' => true,
            'xp_towards'         => 1,
        ]);

        $character = $this->character->refresh();

        $this->assertEquals($this->fetchSkill('Dodge', $character)->xp_towards, 1);

        $this->actingAs($character->user)->visitRoute('game.character.sheet')->post(route('train.skill', [
            'character' => $character->id
        ]), [
            'skill_id'      => $this->fetchSkill('Dodge', $character)->id,
            'xp_percentage' => 0.10,
        ]);

        $character = $this->character->refresh();

        $this->assertEquals($this->fetchSkill('Dodge', $character)->xp_towards, 0.10);
    }

    public function testCanCancelTrain() {
        $this->character->skills->where('name', 'Dodge')->first()->update([
            'currently_training' => true,
            'xp_towards'         => 1,
        ]);

        $this->assertEquals($this->fetchSkill('Dodge', $this->character)->xp_towards, 1);

        $this->actingAs($this->character->user)->visitRoute('game.character.sheet')->post(route('cancel.train.skill', [
            'skill' => $this->fetchSkill('Dodge', $this->character)->id,
        ]));

        $this->character->refresh();

        $this->assertEquals($this->fetchSkill('Dodge', $this->character)->xp_towards, 0.0);
        $this->assertFalse($this->fetchSkill('Dodge', $this->character)->currently_training);
    }

    public function testShouldSeeSkillPage() {
        $this->actingAs($this->character->user)->visit(route('skill.character.info', [
            'skill' => $this->fetchSkill('Dodge', $this->character)->id,
        ]))->see('Dodge');
    }

    public function testNotShouldSeeSkillPage() {
        $this->visit(route('skill.character.info', [
            'skill' => $this->fetchSkill('Dodge', $this->character)->id,
        ]))->dontSee('Dodge');
    }

    protected function fetchSkill(string $name, Character $character): Skill {
        return $character->skills()->join('game_skills', function($join) use($name){
            $join->on('game_skills.id', 'skills.game_skill_id')
                 ->where('name', $name);
        })->select('skills.*')->first();
    }
}
