<?php

namespace Tests\Console;

use App\Flare\Models\Character;
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();
    }

    public function test_dry_run_reports_rows_without_applying_changes(): void
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

    public function test_apply_backfills_ended_exploration_logs_with_null_panel_dismissed_at(): void
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

    public function test_apply_backfills_completed_delve_explorations_with_null_panel_dismissed_at(): void
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

    public function test_active_exploration_logs_are_not_backfilled(): void
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

    public function test_active_delve_explorations_are_not_backfilled(): void
    {
        $activeDelve = $this->createDelveAutomation([
            'character_id' => $this->character->id,
            'completed_at' => null,
            'panel_dismissed_at' => null,
        ]);

        $this->artisan('backfill:completed-panel-dismissals', ['--apply' => true]);

        $this->assertNull($activeDelve->fresh()->panel_dismissed_at);
    }

    public function test_command_outputs_nothing_to_backfill_when_empty(): void
    {
        Artisan::call('backfill:completed-panel-dismissals', ['--apply' => true]);

        $this->assertStringContainsString('Nothing to backfill.', Artisan::output());
    }

    public function test_command_outputs_done_summary_after_apply(): void
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
