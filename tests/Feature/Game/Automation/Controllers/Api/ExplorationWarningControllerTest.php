<?php

namespace Tests\Feature\Game\Automation\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\ExplorationLog;
use App\Flare\Models\ExplorationWarning;
use App\Game\Automation\Events\ExplorationWarningState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateExplorationLog;
use Tests\Traits\CreateExplorationWarning;

class ExplorationWarningControllerTest extends TestCase
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

    public function testDismissSoftDismissesLatestWarning(): void
    {
        Event::fake();

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
            ->call('POST', '/api/exploration/' . $this->character->id . '/warning/dismiss', [
                '_token' => csrf_token(),
            ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($response->json('type'));
        $this->assertNull($response->json('output'));
        $this->assertNotNull($warning->fresh());
        $this->assertNotNull($warning->fresh()->dismissed_at);
        $this->assertNotNull(ExplorationLog::find($log->id));
        $this->assertNotNull(ExplorationLog::find($log->id)->panel_dismissed_at);
    }

    public function testDismissSoftDismissesAllWarningsForCharacter(): void
    {
        Event::fake();

        $olderLog = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'ended_at' => now(),
        ]);

        $olderWarning = $this->createExplorationWarning([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'exploration_log_id' => $olderLog->id,
            'type' => 'fight',
            'message' => 'Older warning.',
        ]);

        $newerLog = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'ended_at' => now(),
        ]);

        $newerWarning = $this->createExplorationWarning([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'exploration_log_id' => $newerLog->id,
            'type' => 'fight',
            'message' => 'Newer warning.',
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/exploration/' . $this->character->id . '/warning/dismiss', [
                '_token' => csrf_token(),
                'warning_id' => $olderWarning->id,
            ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotNull($olderWarning->fresh());
        $this->assertNotNull($olderWarning->fresh()->dismissed_at);
        $this->assertNotNull(ExplorationLog::find($olderLog->id));
        $this->assertNotNull(ExplorationLog::find($olderLog->id)->panel_dismissed_at);
        $this->assertNotNull($newerWarning->fresh());
        $this->assertNotNull($newerWarning->fresh()->dismissed_at);
        $this->assertNotNull(ExplorationLog::find($newerLog->id));
        $this->assertNotNull(ExplorationLog::find($newerLog->id)->panel_dismissed_at);
    }

    public function testDismissBroadcastsClearedWarningState(): void
    {
        Event::fake();

        $log = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'ended_at' => now(),
        ]);

        $this->createExplorationWarning([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'exploration_log_id' => $log->id,
            'type' => 'fight',
            'message' => 'Something went wrong.',
        ]);

        $this->actingAs($this->character->user)
            ->call('POST', '/api/exploration/' . $this->character->id . '/warning/dismiss', [
                '_token' => csrf_token(),
            ]);

        Event::assertDispatched(ExplorationWarningState::class, function (ExplorationWarningState $event): bool {
            return $event->has_warning === false && $event->warnings === [];
        });
    }

    public function testDismissRejectsWrongCharacter(): void
    {
        $otherCharacter = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $response = $this->actingAs($otherCharacter->user)
            ->call('POST', '/api/exploration/' . $this->character->id . '/warning/dismiss', [
                '_token' => csrf_token(),
            ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals(0, ExplorationWarning::where('character_id', $this->character->id)->count());
    }
}
