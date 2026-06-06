<?php

namespace Tests\Feature\Game\Automation\Controllers\Api;

use App\Flare\Models\Character;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateExplorationLog;
use Tests\Traits\CreateExplorationWarning;

class ExplorationOutputControllerTest extends TestCase
{
    use CreateExplorationLog;
    use CreateExplorationWarning;
    use RefreshDatabase;

    private Character $character;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testOutputReturnsActiveExplorationLog(): void
    {
        $log = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'ended_at' => null,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/exploration/' . $this->character->id . '/output');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('active', $response->json('type'));
        $this->assertEquals($log->id, $response->json('output.id'));
        $this->assertEquals($this->character->id, $response->json('output.character_id'));
    }

    public function testOutputReturnsEndedWarningOutput(): void
    {
        $log = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'ended_at' => now(),
        ]);

        $warning = $this->createExplorationWarning([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'exploration_log_id' => $log->id,
            'type' => 'fight',
            'message' => 'Something went wrong.',
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/exploration/' . $this->character->id . '/output');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('warning', $response->json('type'));
        $this->assertEquals($warning->id, $response->json('output.id'));
        $this->assertEquals($this->character->id, $response->json('output.character_id'));
        $this->assertEquals('fight', $response->json('output.type'));
        $this->assertEquals('Something went wrong.', $response->json('output.message'));
    }

    public function testOutputReturnsNullStateWhenNeitherExists(): void
    {
        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/exploration/' . $this->character->id . '/output');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($response->json('type'));
        $this->assertNull($response->json('output'));
    }

    public function testOutputRejectsWrongCharacter(): void
    {
        $otherCharacter = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $response = $this->actingAs($otherCharacter->user)
            ->call('GET', '/api/exploration/' . $this->character->id . '/output', [], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);

        $this->assertEquals(422, $response->getStatusCode());
    }
}
