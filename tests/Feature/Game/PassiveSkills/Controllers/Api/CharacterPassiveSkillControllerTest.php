<?php

namespace Tests\Feature\Game\PassiveSkills\Controllers\Api;

use App\Game\Automation\Values\AutomationType;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterAutomation;

class CharacterPassiveSkillControllerTest extends TestCase
{
    use CreateCharacterAutomation, RefreshDatabase;

    private ?CharacterFactory $character = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_exploration_allows_kingdom_passives(): void
    {
        $character = $this->character->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/kingdom-passives/'.$character->id);

        $jsonData = json_decode($response->getContent(), true);

        $response->assertStatus(200);
        $this->assertNotEmpty($jsonData['kingdom_passives']);
    }

    public function test_exploration_blocks_passive_training(): void
    {
        $character = $this->character->getCharacter();
        $passive = $character->passiveSkills()->first();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/train/passive/'.$passive->id.'/'.$character->id);

        $jsonData = json_decode($response->getContent(), true);

        $response->assertStatus(422);
        $this->assertEquals('You cannot do that while Exploration automation is running. Cancel it first.', $jsonData['message']);
    }

    public function test_exploration_blocks_stopping_passive_training(): void
    {
        $character = $this->character->getCharacter();
        $passive = $character->passiveSkills()->first();

        $passive->update([
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/stop-training/passive/'.$passive->id.'/'.$character->id);

        $jsonData = json_decode($response->getContent(), true);

        $response->assertStatus(422);
        $this->assertEquals('You cannot do that while Exploration automation is running. Cancel it first.', $jsonData['message']);
    }

    public function test_train_skill_blocks_when_character_does_not_own_the_passive_skill(): void
    {
        $character = $this->character->getCharacter();
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $otherPassive = $otherCharacter->passiveSkills()->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/train/passive/'.$otherPassive->id.'/'.$character->id);

        $jsonData = json_decode($response->getContent(), true);

        $response->assertStatus(422);
        $this->assertEquals('You do not own that.', $jsonData['message']);
    }

    public function test_train_skill_returns_already_maxed_message_when_skill_is_maxed(): void
    {
        $characterFactory = $this->character->passiveSkillManagement()->assignPassiveSkill(
            PassiveSkillTypeValue::KINGDOM_DEFENCE,
            1,
            ['max_level' => 1],
        )->getCharacterFactory();

        $character = $characterFactory->getCharacter();
        $passive = $character->passiveSkills()->latest('id')->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/train/passive/'.$passive->id.'/'.$character->id);

        $jsonData = json_decode($response->getContent(), true);

        $response->assertStatus(422);
        $this->assertEquals('This passive skill is already maxed and cannot be trained.', $jsonData['message']);
    }

    public function test_train_skill_blocks_when_another_passive_is_already_training(): void
    {
        $characterFactory = $this->character->passiveSkillManagement()->assignPassiveSkill(
            PassiveSkillTypeValue::KINGDOM_DEFENCE,
            0,
            [],
            ['started_at' => now()],
        )->getCharacterFactory();

        $character = $characterFactory->getCharacter();
        $passiveToTrain = $character->passiveSkills()->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/train/passive/'.$passiveToTrain->id.'/'.$character->id);

        $jsonData = json_decode($response->getContent(), true);

        $response->assertStatus(422);
        $this->assertEquals('Only one passive allowed to train at a time.', $jsonData['message']);
    }

    public function test_train_skill_starts_training_and_returns_kingdom_passives(): void
    {
        Queue::fake();

        $character = $this->character->getCharacter();
        $passive = $character->passiveSkills()->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/train/passive/'.$passive->id.'/'.$character->id);

        $jsonData = json_decode($response->getContent(), true);

        $response->assertStatus(200);
        $this->assertStringStartsWith('Started training', $jsonData['message']);
        $this->assertArrayHasKey('kingdom_passives', $jsonData);
        $this->assertArrayHasKey('passive_training', $jsonData);
    }

    public function test_stop_training_blocks_when_character_does_not_own_the_passive_skill(): void
    {
        $character = $this->character->getCharacter();
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $otherPassive = $otherCharacter->passiveSkills()->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/stop-training/passive/'.$otherPassive->id.'/'.$character->id);

        $jsonData = json_decode($response->getContent(), true);

        $response->assertStatus(422);
        $this->assertEquals('You do not own that.', $jsonData['message']);
    }

    public function test_stop_training_stops_the_passive_and_returns_kingdom_passives(): void
    {
        $character = $this->character->getCharacter();
        $passive = $character->passiveSkills()->first();

        $passive->update([
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/stop-training/passive/'.$passive->id.'/'.$character->id);

        $jsonData = json_decode($response->getContent(), true);

        $response->assertStatus(200);
        $this->assertStringStartsWith('Stopped training', $jsonData['message']);
        $this->assertArrayHasKey('kingdom_passives', $jsonData);
        $this->assertNull($passive->refresh()->started_at);
    }
}
