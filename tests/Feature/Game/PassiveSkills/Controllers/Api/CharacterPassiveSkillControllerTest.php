<?php

namespace Tests\Feature\Game\PassiveSkills\Controllers\Api;

use App\Flare\Models\CharacterAutomation;
use App\Flare\Values\AutomationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CharacterPassiveSkillControllerTest extends TestCase
{
    use RefreshDatabase;

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

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
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

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
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

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/stop-training/passive/'.$passive->id.'/'.$character->id);

        $jsonData = json_decode($response->getContent(), true);

        $response->assertStatus(422);
        $this->assertEquals('You cannot do that while Exploration automation is running. Cancel it first.', $jsonData['message']);
    }
}
