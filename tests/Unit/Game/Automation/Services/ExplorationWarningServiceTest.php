<?php

namespace Tests\Unit\Game\Automation\Services;

use App\Flare\Models\Character;
use App\Flare\Models\ExplorationLog;
use App\Flare\Models\ExplorationWarning;
use App\Game\Automation\Events\ExplorationWarningState;
use App\Game\Automation\Services\ExplorationWarningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateExplorationLog;
use Tests\Traits\CreateExplorationWarning;

class ExplorationWarningServiceTest extends TestCase
{
    use CreateExplorationLog;
    use CreateExplorationWarning;
    use RefreshDatabase;

    private Character $character;

    private ExplorationWarningService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $this->service = new ExplorationWarningService();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_create_warning_returns_state(): void
    {
        Event::fake();

        $log = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
        ]);

        $result = $this->service->createWarning($this->character, $log, 'fight', 'Something went wrong.');

        $this->assertTrue($result['has_warning']);
        $this->assertCount(1, $result['warnings']);
        $this->assertEquals('fight', $result['warnings'][0]['type']);
        $this->assertEquals('Something went wrong.', $result['warnings'][0]['message']);
        $this->assertEquals(1, ExplorationWarning::where('character_id', $this->character->id)->count());

        Event::assertDispatched(ExplorationWarningState::class, function (ExplorationWarningState $event): bool {
            return $event->has_warning === true;
        });
    }

    public function test_get_state_returns_only_the_newest_warning(): void
    {
        Event::fake();

        $olderLog = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
        ]);

        $newerLog = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
        ]);

        $olderWarning = $this->createExplorationWarning([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'exploration_log_id' => $olderLog->id,
            'type' => 'older',
            'message' => 'Older warning.',
        ]);

        $newerWarning = $this->createExplorationWarning([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'exploration_log_id' => $newerLog->id,
            'type' => 'newer',
            'message' => 'Newer warning.',
        ]);

        $result = $this->service->getState($this->character);

        $this->assertTrue($result['has_warning']);
        $this->assertCount(1, $result['warnings']);
        $this->assertEquals($newerWarning->id, $result['warnings'][0]['id']);
        $this->assertEquals('newer', $result['warnings'][0]['type']);
        $this->assertEquals('Newer warning.', $result['warnings'][0]['message']);
        $this->assertNotEquals($olderWarning->id, $result['warnings'][0]['id']);

        Event::assertDispatched(ExplorationWarningState::class, function (ExplorationWarningState $event) use ($newerWarning, $olderWarning): bool {
            return $event->has_warning === true
                && count($event->warnings) === 1
                && $event->warnings[0]['id'] === $newerWarning->id
                && $event->warnings[0]['id'] !== $olderWarning->id;
        });
    }

    public function test_dismiss_latest_soft_dismisses_warning_and_linked_log(): void
    {
        Event::fake();

        $log = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
        ]);

        $warning = $this->createExplorationWarning([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'exploration_log_id' => $log->id,
            'type' => 'fight',
            'message' => 'Something bad happened.',
        ]);

        $result = $this->service->dismissLatest($this->character);

        $this->assertFalse($result['has_warning']);
        $this->assertCount(0, $result['warnings']);
        $this->assertNotNull($warning->fresh());
        $this->assertNotNull($warning->fresh()->dismissed_at);
        $this->assertNotNull(ExplorationLog::find($log->id));
        $this->assertNotNull(ExplorationLog::find($log->id)->panel_dismissed_at);

        Event::assertDispatched(ExplorationWarningState::class, function (ExplorationWarningState $event): bool {
            return $event->has_warning === false;
        });
    }

    public function test_dismiss_selected_soft_dismisses_warning_and_linked_log(): void
    {
        Event::fake();

        $log = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
        ]);

        $warning = $this->createExplorationWarning([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'exploration_log_id' => $log->id,
            'type' => 'fight',
            'message' => 'Something bad happened.',
        ]);

        $result = $this->service->dismissSelected($this->character, $warning);

        $this->assertFalse($result['has_warning']);
        $this->assertCount(0, $result['warnings']);
        $this->assertNotNull($warning->fresh());
        $this->assertNotNull($warning->fresh()->dismissed_at);
        $this->assertNotNull(ExplorationLog::find($log->id));
        $this->assertNotNull(ExplorationLog::find($log->id)->panel_dismissed_at);

        Event::assertDispatched(ExplorationWarningState::class, function (ExplorationWarningState $event): bool {
            return $event->has_warning === false;
        });
    }

    public function test_dismiss_latest_soft_dismisses_all_warnings_for_character_at_once(): void
    {
        Event::fake();

        $firstLog = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
        ]);

        $secondLog = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
        ]);

        $firstWarning = $this->createExplorationWarning([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'exploration_log_id' => $firstLog->id,
            'type' => 'fight',
            'message' => 'First warning.',
        ]);

        $secondWarning = $this->createExplorationWarning([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'exploration_log_id' => $secondLog->id,
            'type' => 'fight',
            'message' => 'Second warning.',
        ]);

        $result = $this->service->dismissLatest($this->character);

        $this->assertFalse($result['has_warning']);
        $this->assertCount(0, $result['warnings']);
        $this->assertNotNull($firstWarning->fresh()->dismissed_at);
        $this->assertNotNull($secondWarning->fresh()->dismissed_at);
        $this->assertNotNull(ExplorationLog::find($firstLog->id)->panel_dismissed_at);
        $this->assertNotNull(ExplorationLog::find($secondLog->id)->panel_dismissed_at);
    }

    public function test_dismiss_latest_does_nothing_when_no_warning_exists(): void
    {
        Event::fake();

        $result = $this->service->dismissLatest($this->character);

        $this->assertFalse($result['has_warning']);
        $this->assertCount(0, $result['warnings']);

        Event::assertDispatched(ExplorationWarningState::class, function (ExplorationWarningState $event): bool {
            return $event->has_warning === false;
        });
    }
}
