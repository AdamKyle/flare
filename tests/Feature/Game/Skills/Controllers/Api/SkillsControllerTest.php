<?php

namespace Tests\Feature\Game\Skills\Controllers\Api;

use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;

class SkillsControllerTest extends TestCase
{
    use CreateGameSkill, CreateGameSkill, RefreshDatabase;

    private ?CharacterFactory $character = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function testGetSkillsForPlayer()
    {
        $trainingSkill = $this->createGameSkill([
            'name' => 'training skill',
            'type' => SkillTypeValue::TRAINING->value,
            'can_train' => true,
        ]);

        $craftingSkill = $this->createGameSkill([
            'name' => 'crafting skill',
            'type' => SkillTypeValue::CRAFTING->value,
            'can_train' => false,
        ]);

        $character = $this->character->assignSkill($trainingSkill)->assignSkill($craftingSkill)->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/skills/' . $character->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertNotEmpty($jsonData['training_skills']);
        $this->assertNotEmpty($jsonData['crafting_skills']);
    }

    public function testFailToGetSkillInformation()
    {

        $trainingSkill = $this->createGameSkill([
            'name' => 'training skill',
            'type' => SkillTypeValue::TRAINING
        ]);

        $character = $this->character->getCharacter();

        $secondaryCharacter = (new CharacterFactory())->createBaseCharacter()->assignSkill($trainingSkill)->getCharacter();

        $skill = $secondaryCharacter->skills()->where('game_skill_id', $trainingSkill->id)->first();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/skill/' . $character->id . '/' . $skill->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('No. Not allowed to do that.', $jsonData['message']);
        $this->assertEquals(422, $response->status());
    }

    public function testGetSkillInformation()
    {
        $trainingSkill = $this->createGameSkill([
            'name' => 'training skill',
            'type' => SkillTypeValue::TRAINING
        ]);

        $character = $this->character->assignSkill($trainingSkill)->getCharacter();

        $skill = $character->skills()->where('game_skill_id', $trainingSkill->id)->first();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/skill/' . $character->id . '/' . $skill->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals($jsonData['id'], $skill->id);
    }

    public function testTrainSkill()
    {
        $trainingSkill = $this->createGameSkill([
            'name' => 'training skill',
            'type' => SkillTypeValue::TRAINING
        ]);

        $character = $this->character->assignSkill($trainingSkill)->getCharacter();

        $skill = $character->skills()->where('game_skill_id', $trainingSkill->id)->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/skill/train/' . $character->id, [
                'skill_id' => $skill->id,
                'xp_percentage' => 0.10,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('You are now training: ' . $skill->name, $jsonData['message']);

        $character = $character->refresh();
        $skill = $character->skills()->where('game_skill_id', $trainingSkill->id)->first();

        $this->assertTrue($skill->currently_training);
        $this->assertEquals(0.10, $skill->xp_towards);
    }

    public function testFailToCancelSkillTraining()
    {

        $trainingSkill = $this->createGameSkill([
            'name' => 'training skill',
            'type' => SkillTypeValue::TRAINING
        ]);

        $character = $this->character->getCharacter();

        $secondaryCharacter = (new CharacterFactory())->createBaseCharacter()->assignSkill($trainingSkill)->getCharacter();

        $skill = $secondaryCharacter->skills()->where('game_skill_id', $trainingSkill->id)->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/skill/cancel-train/' . $character->id . '/' . $skill->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Nope. You cannot do that.', $jsonData['message']);
        $this->assertEquals(422, $response->status());
    }

    public function testStopTrainingSkill()
    {
        $trainingSkill = $this->createGameSkill([
            'name' => 'training skill',
            'type' => SkillTypeValue::TRAINING
        ]);

        $character = $this->character->assignSkill($trainingSkill)->getCharacter();

        $character->skills()->where('game_skill_id', $trainingSkill->id)->first()->update([
            'currently_training' => true,
            'xp_percentage' => 0.10,
        ]);

        $character = $character->refresh();

        $skill = $character->skills()->where('game_skill_id', $trainingSkill->id)->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/skill/cancel-train/' . $character->id . '/' . $skill->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('You stopped training: ' . $skill->name, $jsonData['message']);

        $character = $character->refresh();
        $skill = $character->skills()->where('game_skill_id', $trainingSkill->id)->first();

        $this->assertFalse($skill->currently_training);
        $this->assertEquals(0, $skill->xp_towards);
    }
}
