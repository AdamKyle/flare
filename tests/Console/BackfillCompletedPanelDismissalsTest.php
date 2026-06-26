<?php

namespace Tests\Console;

use App\Flare\Models\Character;
use App\Flare\Models\DelveExploration;
use App\Flare\Models\ExplorationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateDelveAutomation;
use Tests\Traits\CreateExplorationLog;

class BackfillCompletedPanelDismissalsTest extends TestCase
{
    use CreateDelveAutomation, CreateExplorationLog, RefreshDatabase;

    private Character $character;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();
    }

    public function testDryRunReportsRowsWithoutApplyingChanges(): void
    {
        $explorationLog = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'ended_at' => now()->subHour(),
            'panel_dismissed_at' => null,
        ]);

        $delveExploration = $this->createDelveAutomation([
            'character_id' => $this->character->id,
            'completed_at' => now()->subHour(),
            'panel_dismissed_at' => null,
        ]);

        $this->artisan('backfill:completed-panel-dismissals');

        $this->assertNull($explorationLog->fresh()->panel_dismissed_at);
        $this->assertNull($delveExploration->fresh()->panel_dismissed_at);
    }

    public function testApplyBackfillsEndedExplorationLogsWithNullPanelDismissedAt(): void
    {
        $endedLog = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'ended_at' => now()->subHour(),
            'panel_dismissed_at' => null,
        ]);

        $alreadyDismissedLog = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'ended_at' => now()->subHour(),
            'panel_dismissed_at' => now()->subMinutes(30),
        ]);

        $this->artisan('backfill:completed-panel-dismissals', ['--apply' => true]);

        $this->assertNotNull($endedLog->fresh()->panel_dismissed_at);
        $this->assertNotNull($alreadyDismissedLog->fresh()->panel_dismissed_at);
        $this->assertEquals(
            $alreadyDismissedLog->panel_dismissed_at->toDateTimeString(),
            $alreadyDismissedLog->fresh()->panel_dismissed_at->toDateTimeString()
        );
    }

    public function testApplyBackfillsCompletedDelveExplorationsWithNullPanelDismissedAt(): void
    {
        $completedDelve = $this->createDelveAutomation([
            'character_id' => $this->character->id,
            'completed_at' => now()->subHour(),
            'panel_dismissed_at' => null,
        ]);

        $alreadyDismissedDelve = $this->createDelveAutomation([
            'character_id' => $this->character->id,
            'completed_at' => now()->subHour(),
            'panel_dismissed_at' => now()->subMinutes(30),
        ]);

        $this->artisan('backfill:completed-panel-dismissals', ['--apply' => true]);

        $this->assertNotNull($completedDelve->fresh()->panel_dismissed_at);
        $this->assertNotNull($alreadyDismissedDelve->fresh()->panel_dismissed_at);
        $this->assertEquals(
            $alreadyDismissedDelve->panel_dismissed_at->toDateTimeString(),
            $alreadyDismissedDelve->fresh()->panel_dismissed_at->toDateTimeString()
        );
    }

    public function testActiveExplorationLogsAreNotBackfilled(): void
    {
        $activeLog = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'ended_at' => null,
            'panel_dismissed_at' => null,
        ]);

        $this->artisan('backfill:completed-panel-dismissals', ['--apply' => true]);

        $this->assertNull($activeLog->fresh()->panel_dismissed_at);
    }

    public function testActiveDelveExplorationsAreNotBackfilled(): void
    {
        $activeDelve = $this->createDelveAutomation([
            'character_id' => $this->character->id,
            'completed_at' => null,
            'panel_dismissed_at' => null,
        ]);

        $this->artisan('backfill:completed-panel-dismissals', ['--apply' => true]);

        $this->assertNull($activeDelve->fresh()->panel_dismissed_at);
    }

    public function testCommandOutputsNothingToBackfillWhenEmpty(): void
    {
        Artisan::call('backfill:completed-panel-dismissals', ['--apply' => true]);

        $this->assertStringContainsString('Nothing to backfill.', Artisan::output());
    }

    public function testCommandOutputsDoneSummaryAfterApply(): void
    {
        $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'ended_at' => now()->subHour(),
            'panel_dismissed_at' => null,
        ]);

        $this->createDelveAutomation([
            'character_id' => $this->character->id,
            'completed_at' => now()->subHour(),
            'panel_dismissed_at' => null,
        ]);

        Artisan::call('backfill:completed-panel-dismissals', ['--apply' => true]);

        $this->assertStringContainsString('Done:', Artisan::output());
    }
}
