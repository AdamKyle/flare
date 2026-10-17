<?php

namespace Tests\Console;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\CharacterBattleRewardRequest;
use App\Flare\Models\CharacterBattleRewardRequestMessage;
use App\Flare\Models\CharacterBattleRewardRequestStep;
use App\Flare\Models\DelveExploration;
use App\Flare\Models\DelveLog;
use App\Flare\Models\ExplorationLog;
use App\Flare\Models\ExplorationWarning;
use App\Flare\Models\Faction;
use App\Flare\Models\FactionLoyalty;
use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Models\FactionLoyaltyAutomationLog;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Flare\Models\GameMap;
use App\Flare\Models\Inventory;
use App\Flare\Models\Monster;
use App\Flare\Models\Npc;
use App\Flare\Models\User;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestStatus;
use App\Flare\Values\AutomationType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CleanHistoricalSystemLogsTest extends TestCase
{
    use RefreshDatabase;

    public function testDryRunLeavesEligibleOldFailedJobsUntouchedAndReportsCount(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $batchCrafting = BatchCrafting::factory()->create([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'completed_at' => now()->subDays(31),
        ]);
        DB::table('failed_jobs')->insert([
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'Test exception',
            'failed_at' => now()->subDays(31),
        ]);

        $this->assertEquals(0, $this->artisan('clean:historical-system-logs', ['--dry-run' => true]));
        $output = Artisan::output();
        $this->assertStringContainsString('Cutoff:', $output);
        $this->assertStringContainsString('Mode: DRY RUN', $output);
        $this->assertStringContainsString('failed_jobs: 1', $output);
        $this->assertStringContainsString('batch_craftings: 1', $output);
        $this->assertStringContainsString('Total: 2', $output);

        $this->assertEquals(1, DB::table('failed_jobs')->count());
        $this->assertTrue(BatchCrafting::whereKey($batchCrafting->id)->exists());
    }

    public function testActualRunDeletesOldFailedJobAndRetainsRecentFailedJob(): void
    {
        DB::table('failed_jobs')->insert([
            [
                'connection' => 'database',
                'queue' => 'default',
                'payload' => '{"age":31}',
                'exception' => 'Old exception',
                'failed_at' => now()->subDays(31),
            ],
            [
                'connection' => 'database',
                'queue' => 'default',
                'payload' => '{"age":29}',
                'exception' => 'Recent exception',
                'failed_at' => now()->subDays(29),
            ],
        ]);

        $this->assertEquals(0, $this->artisan('clean:historical-system-logs'));
        $output = Artisan::output();

        $this->assertStringContainsString('Cutoff:', $output);
        $this->assertStringContainsString('Mode: DELETE', $output);
        $this->assertStringContainsString('Batch size: 500', $output);
        $this->assertStringContainsString('failed_jobs', $output);

        $this->assertFalse(DB::table('failed_jobs')->where('payload', '{"age":31}')->exists());
        $this->assertTrue(DB::table('failed_jobs')->where('payload', '{"age":29}')->exists());
    }

    public function testExactCutoffBoundaryUsesStrictLessThanComparison(): void
    {
        Carbon::setTestNow('2026-10-15 12:00:00');
        DB::table('failed_jobs')->insert([
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{"boundary":true}',
            'exception' => 'Boundary exception',
            'failed_at' => now()->subDays(30),
        ]);

        $this->assertEquals(0, $this->artisan('clean:historical-system-logs'));

        $this->assertTrue(DB::table('failed_jobs')->where('payload', '{"boundary":true}')->exists());
        Carbon::setTestNow();
    }

    public function testBatchSizeZeroIsRejectedWithoutDeletingRows(): void
    {
        DB::table('failed_jobs')->insert([
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'Test exception',
            'failed_at' => now()->subDays(31),
        ]);

        $this->assertEquals(1, $this->artisan('clean:historical-system-logs', ['--batch-size' => '0']));
        $this->assertStringContainsString('The batch size must be a positive integer.', Artisan::output());

        $this->assertEquals(1, DB::table('failed_jobs')->count());
    }

    public function testOldTerminalRewardRequestDeletesChildrenAndParent(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $request = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::COMPLETED,
            'completed_at' => now()->subDays(31),
        ]);
        CharacterBattleRewardRequestStep::factory()->create([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
        ]);
        CharacterBattleRewardRequestMessage::factory()->create([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'user_id' => $user->id,
        ]);

        $this->assertEquals(0, $this->artisan('clean:historical-system-logs'));

        $this->assertFalse(CharacterBattleRewardRequest::whereKey($request->id)->exists());
        $this->assertFalse(CharacterBattleRewardRequestStep::where('character_battle_reward_request_id', $request->id)->exists());
        $this->assertFalse(CharacterBattleRewardRequestMessage::where('character_battle_reward_request_id', $request->id)->exists());
    }

    public function testOldFailedRewardRequestDeletesChildrenAndParent(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $request = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::FAILED,
            'completed_at' => now()->subDays(31),
        ]);
        CharacterBattleRewardRequestStep::factory()->create([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
        ]);
        CharacterBattleRewardRequestMessage::factory()->create([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'user_id' => $user->id,
        ]);

        $this->assertEquals(0, $this->artisan('clean:historical-system-logs'));

        $this->assertFalse(CharacterBattleRewardRequest::whereKey($request->id)->exists());
        $this->assertFalse(CharacterBattleRewardRequestStep::where('character_battle_reward_request_id', $request->id)->exists());
        $this->assertFalse(CharacterBattleRewardRequestMessage::where('character_battle_reward_request_id', $request->id)->exists());
    }

    public function testOldPendingRewardRequestAndChildrenRemain(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $request = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PENDING,
            'created_at' => now()->subDays(31),
        ]);
        CharacterBattleRewardRequestStep::factory()->create([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
        ]);
        CharacterBattleRewardRequestMessage::factory()->create([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'user_id' => $user->id,
        ]);

        $this->assertEquals(0, $this->artisan('clean:historical-system-logs'));

        $this->assertTrue(CharacterBattleRewardRequest::whereKey($request->id)->exists());
        $this->assertTrue(CharacterBattleRewardRequestStep::where('character_battle_reward_request_id', $request->id)->exists());
        $this->assertTrue(CharacterBattleRewardRequestMessage::where('character_battle_reward_request_id', $request->id)->exists());
    }

    public function testOldProcessingRewardRequestAndChildrenRemain(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $request = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PROCESSING,
            'created_at' => now()->subDays(31),
        ]);
        CharacterBattleRewardRequestStep::factory()->create([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
        ]);
        CharacterBattleRewardRequestMessage::factory()->create([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'user_id' => $user->id,
        ]);

        $this->assertEquals(0, $this->artisan('clean:historical-system-logs'));

        $this->assertTrue(CharacterBattleRewardRequest::whereKey($request->id)->exists());
        $this->assertTrue(CharacterBattleRewardRequestStep::where('character_battle_reward_request_id', $request->id)->exists());
        $this->assertTrue(CharacterBattleRewardRequestMessage::where('character_battle_reward_request_id', $request->id)->exists());
    }

    public function testOldResumableRewardRequestAndChildrenRemain(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $request = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::RESUMABLE,
            'created_at' => now()->subDays(31),
        ]);
        CharacterBattleRewardRequestStep::factory()->create([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
        ]);
        CharacterBattleRewardRequestMessage::factory()->create([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'user_id' => $user->id,
        ]);

        $this->assertEquals(0, $this->artisan('clean:historical-system-logs'));

        $this->assertTrue(CharacterBattleRewardRequest::whereKey($request->id)->exists());
        $this->assertTrue(CharacterBattleRewardRequestStep::where('character_battle_reward_request_id', $request->id)->exists());
        $this->assertTrue(CharacterBattleRewardRequestMessage::where('character_battle_reward_request_id', $request->id)->exists());
    }

    public function testRecentlyCompletedRewardRequestRemains(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $request = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::COMPLETED,
            'completed_at' => now()->subDays(29),
        ]);

        $this->assertEquals(0, $this->artisan('clean:historical-system-logs'));

        $this->assertTrue(CharacterBattleRewardRequest::whereKey($request->id)->exists());
    }

    public function testOldExplorationLogSurvivesUnchanged(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $monster = Monster::factory()->create();
        $automation = CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
        ]);
        $log = ExplorationLog::factory()->create([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'character_automation_id' => $automation->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subDays(60),
            'fights' => 12,
            'kills' => 10,
        ]);

        $this->assertEquals(0, $this->artisan('clean:historical-system-logs'));

        $this->assertTrue(ExplorationLog::whereKey($log->id)->where('fights', 12)->where('kills', 10)->exists());
    }

    public function testOldRunningBatchCraftingRowSurvivesUnchanged(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $batchCrafting = BatchCrafting::factory()->create([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'started_at' => now()->subDays(60),
            'completed_at' => null,
            'cancelled_at' => null,
        ]);

        $this->assertEquals(0, $this->artisan('clean:historical-system-logs'));

        $this->assertTrue(BatchCrafting::whereKey($batchCrafting->id)->whereNull('completed_at')->whereNull('cancelled_at')->exists());
    }

    public function testOldCompletedBatchCraftingIsDeletedWhileRecentCompletedBatchCraftingRemains(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $oldBatchCrafting = BatchCrafting::factory()->create([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'completed_at' => now()->subDays(31),
        ]);
        $recentBatchCrafting = BatchCrafting::factory()->create([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'completed_at' => now()->subDays(29),
        ]);

        $this->assertEquals(0, $this->artisan('clean:historical-system-logs'));

        $this->assertFalse(BatchCrafting::whereKey($oldBatchCrafting->id)->exists());
        $this->assertTrue(BatchCrafting::whereKey($recentBatchCrafting->id)->exists());
    }

    public function testOldCancelledBatchCraftingIsDeleted(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $batchCrafting = BatchCrafting::factory()->create([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'cancelled_at' => now()->subDays(31),
        ]);

        $this->assertEquals(0, $this->artisan('clean:historical-system-logs'));

        $this->assertFalse(BatchCrafting::whereKey($batchCrafting->id)->exists());
    }

    public function testLeaderboardCharacterInventoryAndFactionRowsAreNotRemoved(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $inventory = Inventory::factory()->create(['character_id' => $character->id]);
        $gameMap = GameMap::factory()->create();
        $faction = Faction::create([
            'character_id' => $character->id,
            'game_map_id' => $gameMap->id,
            'current_level' => 1,
            'current_points' => 0,
            'points_needed' => 100,
            'maxed' => false,
            'title' => null,
        ]);
        $monster = Monster::factory()->create(['game_map_id' => $gameMap->id]);
        $automation = CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
        ]);
        $log = ExplorationLog::factory()->create([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'character_automation_id' => $automation->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subDays(60),
        ]);

        $this->assertEquals(0, $this->artisan('clean:historical-system-logs'));

        $this->assertTrue(Character::whereKey($character->id)->exists());
        $this->assertTrue(Inventory::whereKey($inventory->id)->exists());
        $this->assertTrue(Faction::whereKey($faction->id)->exists());
        $this->assertTrue(ExplorationLog::whereKey($log->id)->exists());
    }

    public function testOldDismissedExplorationWarningWithoutActiveExplorationIsDeleted(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $monster = Monster::factory()->create();
        $automation = CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'type' => AutomationType::EXPLORING,
            'completed_at' => now()->subDays(31),
        ]);
        $log = ExplorationLog::factory()->create([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'character_automation_id' => $automation->id,
            'monster_id' => $monster->id,
        ]);
        $warning = ExplorationWarning::factory()->create([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'exploration_log_id' => $log->id,
            'dismissed_at' => now()->subDays(31),
            'created_at' => now()->subDays(31),
        ]);

        $this->assertEquals(0, $this->artisan('clean:historical-system-logs'));

        $this->assertFalse(ExplorationWarning::whereKey($warning->id)->exists());
    }

    public function testOldUndismissedExplorationWarningIsRetained(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $monster = Monster::factory()->create();
        $automation = CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'type' => AutomationType::EXPLORING,
            'completed_at' => now()->subDays(31),
        ]);
        $log = ExplorationLog::factory()->create([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'character_automation_id' => $automation->id,
            'monster_id' => $monster->id,
        ]);
        $warning = ExplorationWarning::factory()->create([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'exploration_log_id' => $log->id,
            'dismissed_at' => null,
            'created_at' => now()->subDays(31),
        ]);

        $this->assertEquals(0, $this->artisan('clean:historical-system-logs'));

        $this->assertTrue(ExplorationWarning::whereKey($warning->id)->exists());
    }

    public function testRecentDismissedExplorationWarningIsRetained(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $monster = Monster::factory()->create();
        $automation = CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'type' => AutomationType::EXPLORING,
            'completed_at' => now()->subDays(31),
        ]);
        $log = ExplorationLog::factory()->create([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'character_automation_id' => $automation->id,
            'monster_id' => $monster->id,
        ]);
        $warning = ExplorationWarning::factory()->create([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'exploration_log_id' => $log->id,
            'dismissed_at' => now()->subDays(2),
            'created_at' => now()->subDays(29),
        ]);

        $this->assertEquals(0, $this->artisan('clean:historical-system-logs'));

        $this->assertTrue(ExplorationWarning::whereKey($warning->id)->exists());
    }

    public function testOldDismissedExplorationWarningForActiveExplorationIsRetained(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $monster = Monster::factory()->create();
        $automation = CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'type' => AutomationType::EXPLORING,
            'completed_at' => now()->addHour(),
        ]);
        $log = ExplorationLog::factory()->create([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'character_automation_id' => $automation->id,
            'monster_id' => $monster->id,
        ]);
        $warning = ExplorationWarning::factory()->create([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'exploration_log_id' => $log->id,
            'dismissed_at' => now()->subDays(31),
            'created_at' => now()->subDays(31),
        ]);

        $this->assertEquals(0, $this->artisan('clean:historical-system-logs'));

        $this->assertTrue(ExplorationWarning::whereKey($warning->id)->exists());
    }

    public function testOldCompletedFactionAutomationLogIsDeletedWithoutItsParent(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $automation = CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'completed_at' => now()->subDays(31),
        ]);
        $factionMap = GameMap::factory()->create();
        $faction = Faction::create([
            'character_id' => $character->id,
            'game_map_id' => $factionMap->id,
            'current_level' => 1,
            'current_points' => 0,
            'points_needed' => 100,
            'maxed' => false,
            'title' => null,
        ]);
        $factionLoyalty = FactionLoyalty::factory()->create([
            'character_id' => $character->id,
            'faction_id' => $faction->id,
        ]);
        $factionLoyaltyNpc = FactionLoyaltyNpc::factory()->create([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => Npc::factory()->create()->id,
        ]);
        $factionAutomation = FactionLoyaltyAutomation::factory()->create([
            'character_automation_id' => $automation->id,
            'character_id' => $character->id,
            'faction_loyalty_npc_id' => $factionLoyaltyNpc->id,
            'completed_at' => now()->subDays(31),
        ]);
        $log = FactionLoyaltyAutomationLog::factory()->create([
            'faction_loyalty_automation_id' => $factionAutomation->id,
        ]);

        $this->assertEquals(0, $this->artisan('clean:historical-system-logs'));

        $this->assertFalse(FactionLoyaltyAutomationLog::whereKey($log->id)->exists());
        $this->assertTrue(FactionLoyaltyAutomation::whereKey($factionAutomation->id)->exists());
    }

    public function testOldFactionAutomationLogWithActiveAutomationIsRetained(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $automation = CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'completed_at' => now()->addHour(),
        ]);
        $factionMap = GameMap::factory()->create();
        $faction = Faction::create([
            'character_id' => $character->id,
            'game_map_id' => $factionMap->id,
            'current_level' => 1,
            'current_points' => 0,
            'points_needed' => 100,
            'maxed' => false,
            'title' => null,
        ]);
        $factionLoyalty = FactionLoyalty::factory()->create([
            'character_id' => $character->id,
            'faction_id' => $faction->id,
        ]);
        $factionLoyaltyNpc = FactionLoyaltyNpc::factory()->create([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => Npc::factory()->create()->id,
        ]);
        $factionAutomation = FactionLoyaltyAutomation::factory()->create([
            'character_automation_id' => $automation->id,
            'character_id' => $character->id,
            'faction_loyalty_npc_id' => $factionLoyaltyNpc->id,
            'completed_at' => now()->subDays(31),
        ]);
        $log = FactionLoyaltyAutomationLog::factory()->create([
            'faction_loyalty_automation_id' => $factionAutomation->id,
        ]);

        $this->assertEquals(0, $this->artisan('clean:historical-system-logs'));

        $this->assertTrue(FactionLoyaltyAutomationLog::whereKey($log->id)->exists());
    }

    public function testUnfinishedFactionAutomationLogIsRetained(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $automation = CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'completed_at' => now()->addHour(),
        ]);
        $gameMap = GameMap::factory()->create();
        $faction = Faction::create([
            'character_id' => $character->id,
            'game_map_id' => $gameMap->id,
            'current_level' => 1,
            'current_points' => 0,
            'points_needed' => 100,
            'maxed' => false,
            'title' => null,
        ]);
        $factionLoyalty = FactionLoyalty::factory()->create([
            'character_id' => $character->id,
            'faction_id' => $faction->id,
        ]);
        $factionLoyaltyNpc = FactionLoyaltyNpc::factory()->create([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => Npc::factory()->create()->id,
        ]);
        $factionAutomation = FactionLoyaltyAutomation::factory()->create([
            'character_automation_id' => $automation->id,
            'character_id' => $character->id,
            'faction_loyalty_npc_id' => $factionLoyaltyNpc->id,
            'completed_at' => null,
        ]);
        $log = FactionLoyaltyAutomationLog::factory()->create([
            'faction_loyalty_automation_id' => $factionAutomation->id,
        ]);

        $this->assertEquals(0, $this->artisan('clean:historical-system-logs'));

        $this->assertTrue(FactionLoyaltyAutomationLog::whereKey($log->id)->exists());
        $this->assertTrue(FactionLoyaltyAutomation::whereKey($factionAutomation->id)->exists());
    }

    public function testRecentCompletedFactionAutomationLogIsRetained(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $automation = CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'completed_at' => now()->subDays(2),
        ]);
        $factionMap = GameMap::factory()->create();
        $faction = Faction::create([
            'character_id' => $character->id,
            'game_map_id' => $factionMap->id,
            'current_level' => 1,
            'current_points' => 0,
            'points_needed' => 100,
            'maxed' => false,
            'title' => null,
        ]);
        $factionLoyalty = FactionLoyalty::factory()->create([
            'character_id' => $character->id,
            'faction_id' => $faction->id,
        ]);
        $factionLoyaltyNpc = FactionLoyaltyNpc::factory()->create([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => Npc::factory()->create()->id,
        ]);
        $factionAutomation = FactionLoyaltyAutomation::factory()->create([
            'character_automation_id' => $automation->id,
            'character_id' => $character->id,
            'faction_loyalty_npc_id' => $factionLoyaltyNpc->id,
            'completed_at' => now()->subDays(29),
        ]);
        $log = FactionLoyaltyAutomationLog::factory()->create([
            'faction_loyalty_automation_id' => $factionAutomation->id,
        ]);

        $this->assertEquals(0, $this->artisan('clean:historical-system-logs'));

        $this->assertTrue(FactionLoyaltyAutomationLog::whereKey($log->id)->exists());
    }

    public function testDryRunReportsNewEligibleTypesWithoutMutation(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $monster = Monster::factory()->create();
        $explorationAutomation = CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'type' => AutomationType::EXPLORING,
            'completed_at' => now()->subDays(31),
        ]);
        $explorationLog = ExplorationLog::factory()->create([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'character_automation_id' => $explorationAutomation->id,
            'monster_id' => $monster->id,
        ]);
        $warning = ExplorationWarning::factory()->create([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'exploration_log_id' => $explorationLog->id,
            'dismissed_at' => now()->subDays(31),
            'created_at' => now()->subDays(31),
        ]);
        $factionCharacterAutomation = CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'completed_at' => now()->subDays(31),
        ]);
        $factionMap = GameMap::factory()->create();
        $faction = Faction::create([
            'character_id' => $character->id,
            'game_map_id' => $factionMap->id,
            'current_level' => 1,
            'current_points' => 0,
            'points_needed' => 100,
            'maxed' => false,
            'title' => null,
        ]);
        $factionLoyalty = FactionLoyalty::factory()->create([
            'character_id' => $character->id,
            'faction_id' => $faction->id,
        ]);
        $factionLoyaltyNpc = FactionLoyaltyNpc::factory()->create([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => Npc::factory()->create()->id,
        ]);
        $factionAutomation = FactionLoyaltyAutomation::factory()->create([
            'character_automation_id' => $factionCharacterAutomation->id,
            'character_id' => $character->id,
            'faction_loyalty_npc_id' => $factionLoyaltyNpc->id,
            'completed_at' => now()->subDays(31),
        ]);
        $factionLog = FactionLoyaltyAutomationLog::factory()->create([
            'faction_loyalty_automation_id' => $factionAutomation->id,
        ]);

        $this->assertEquals(0, $this->artisan('clean:historical-system-logs', ['--dry-run' => true]));
        $output = Artisan::output();

        $this->assertStringContainsString('exploration_warnings: 1', $output);
        $this->assertStringContainsString('faction_loyalty_automation_logs: 1', $output);
        $this->assertTrue(ExplorationWarning::whereKey($warning->id)->exists());
        $this->assertTrue(FactionLoyaltyAutomationLog::whereKey($factionLog->id)->exists());
    }

    public function testBatchSizeOneDeletesMultipleEligibleRowsForNewTypes(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $monster = Monster::factory()->create();
        $explorationAutomation = CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'type' => AutomationType::EXPLORING,
            'completed_at' => now()->subDays(31),
        ]);
        $explorationLog = ExplorationLog::factory()->create([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'character_automation_id' => $explorationAutomation->id,
            'monster_id' => $monster->id,
        ]);
        ExplorationWarning::factory()->count(2)->create([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'exploration_log_id' => $explorationLog->id,
            'dismissed_at' => now()->subDays(31),
            'created_at' => now()->subDays(31),
        ]);
        $factionCharacterAutomation = CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'completed_at' => now()->subDays(31),
        ]);
        $factionMap = GameMap::factory()->create();
        $faction = Faction::create([
            'character_id' => $character->id,
            'game_map_id' => $factionMap->id,
            'current_level' => 1,
            'current_points' => 0,
            'points_needed' => 100,
            'maxed' => false,
            'title' => null,
        ]);
        $factionLoyalty = FactionLoyalty::factory()->create([
            'character_id' => $character->id,
            'faction_id' => $faction->id,
        ]);
        $factionLoyaltyNpc = FactionLoyaltyNpc::factory()->create([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => Npc::factory()->create()->id,
        ]);
        $firstFactionAutomation = FactionLoyaltyAutomation::factory()->create([
            'character_automation_id' => $factionCharacterAutomation->id,
            'character_id' => $character->id,
            'faction_loyalty_npc_id' => $factionLoyaltyNpc->id,
            'completed_at' => now()->subDays(31),
        ]);
        $secondFactionAutomation = FactionLoyaltyAutomation::factory()->create([
            'character_automation_id' => $factionCharacterAutomation->id,
            'character_id' => $character->id,
            'faction_loyalty_npc_id' => $factionLoyaltyNpc->id,
            'completed_at' => now()->subDays(31),
        ]);
        FactionLoyaltyAutomationLog::factory()->create([
            'faction_loyalty_automation_id' => $firstFactionAutomation->id,
        ]);
        FactionLoyaltyAutomationLog::factory()->create([
            'faction_loyalty_automation_id' => $secondFactionAutomation->id,
        ]);

        $this->assertEquals(0, $this->artisan('clean:historical-system-logs', ['--batch-size' => '1']));

        $this->assertEquals(0, ExplorationWarning::count());
        $this->assertEquals(0, FactionLoyaltyAutomationLog::count());
    }

    public function testCurrentDelveActivityAndLogsAreRetained(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $monster = Monster::factory()->create();
        $delveExploration = DelveExploration::factory()->create([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subDays(31),
            'completed_at' => null,
        ]);
        $delveLog = DelveLog::factory()->create([
            'character_id' => $character->id,
            'delve_exploration_id' => $delveExploration->id,
        ]);

        $this->assertEquals(0, $this->artisan('clean:historical-system-logs'));

        $this->assertTrue(DelveExploration::whereKey($delveExploration->id)->whereNull('completed_at')->exists());
        $this->assertTrue(DelveLog::whereKey($delveLog->id)->exists());
    }

    public function testRepeatedInvocationSucceedsAndRetainsProtectedRows(): void
    {
        DB::table('failed_jobs')->insert([
            [
                'connection' => 'database',
                'queue' => 'default',
                'payload' => '{"old":true}',
                'exception' => 'Old exception',
                'failed_at' => now()->subDays(31),
            ],
            [
                'connection' => 'database',
                'queue' => 'default',
                'payload' => '{"recent":true}',
                'exception' => 'Recent exception',
                'failed_at' => now()->subDays(2),
            ],
        ]);

        $this->assertEquals(0, $this->artisan('clean:historical-system-logs'));
        $this->assertEquals(0, $this->artisan('clean:historical-system-logs'));

        $this->assertFalse(DB::table('failed_jobs')->where('payload', '{"old":true}')->exists());
        $this->assertTrue(DB::table('failed_jobs')->where('payload', '{"recent":true}')->exists());
    }
}
