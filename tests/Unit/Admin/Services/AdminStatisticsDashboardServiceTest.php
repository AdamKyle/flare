<?php

namespace Tests\Unit\Admin\Services;

use App\Admin\Services\AdminStatisticsDashboardService;
use App\Flare\Jobs\AccountDeletionJob;
use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\GuideQuest;
use App\Flare\Models\InactiveUserDeletionStatistic;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Map;
use App\Flare\Models\Npc;
use App\Flare\Models\Quest;
use App\Flare\Models\QuestsCompleted;
use App\Flare\Models\UserLoginDuration;
use App\Flare\Models\UserSiteAccessStatistics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class AdminStatisticsDashboardServiceTest extends TestCase
{
    use CreateUser, RefreshDatabase;

    public function test_highest_level_character_orders_by_character_level(): void
    {
        $lowLevelRichUser = $this->createUser();
        $highLevelUser = $this->createUser();
        Character::factory()->create(['user_id' => $lowLevelRichUser->id, 'name' => 'Low Level Rich', 'level' => 10, 'gold' => 999999]);
        Character::factory()->create(['user_id' => $highLevelUser->id, 'name' => 'Highest Level', 'level' => 500, 'gold' => 1]);

        $snapshot = resolve(AdminStatisticsDashboardService::class)->snapshot();

        $this->assertSame('Highest Level', $snapshot['summary']['highest_level_character']['name']);
        $this->assertSame(500, $snapshot['summary']['highest_level_character']['level']);
    }

    public function test_richest_character_orders_by_character_gold(): void
    {
        $richUser = $this->createUser();
        $highLevelUser = $this->createUser();
        Character::factory()->create(['user_id' => $richUser->id, 'name' => 'Richest Character', 'level' => 5, 'gold' => 9000]);
        Character::factory()->create(['user_id' => $highLevelUser->id, 'name' => 'High Level Poor', 'level' => 900, 'gold' => 3]);

        $snapshot = resolve(AdminStatisticsDashboardService::class)->snapshot();

        $this->assertSame('Richest Character', $snapshot['summary']['richest_character']['name']);
        $this->assertSame(9000, $snapshot['summary']['richest_character']['gold']);
    }

    public function test_average_login_duration_excludes_open_sessions(): void
    {
        $user = $this->createUser();
        UserLoginDuration::factory()->create([
            'user_id' => $user->id,
            'logged_in_at' => now()->startOfDay(),
            'logged_out_at' => now()->startOfDay()->addHour(),
            'last_activity' => now()->startOfDay()->addHour(),
            'last_heart_beat' => now()->startOfDay()->addHour(),
            'duration_in_seconds' => 3600,
        ]);
        UserLoginDuration::factory()->create([
            'user_id' => $user->id,
            'logged_in_at' => now()->startOfDay(),
            'last_activity' => now()->startOfDay(),
            'last_heart_beat' => now()->startOfDay(),
            'duration_in_seconds' => null,
        ]);

        $snapshot = resolve(AdminStatisticsDashboardService::class)->snapshot();

        $this->assertSame(60.0, $snapshot['login_duration_chart']['points'][0]['value']);
    }

    public function test_snapshot_includes_login_participation_data(): void
    {
        $this->createUser();

        $snapshot = resolve(AdminStatisticsDashboardService::class)->snapshot();

        $this->assertArrayHasKey('login_participation_summary', $snapshot);
        $this->assertArrayHasKey('login_participation_chart', $snapshot);
        $this->assertArrayHasKey('series', $snapshot['login_participation_chart']);
        $this->assertSame('Login Participation', $snapshot['login_participation_chart']['series'][0]['label']);
    }

    public function test_login_participation_percentage_uses_distinct_users_and_documented_denominator(): void
    {
        $firstUser = $this->createUser();
        $this->createUser();
        $admin = $this->createAdmin(Role::create(['name' => 'Admin']));
        UserLoginDuration::factory()->create([
            'user_id' => $firstUser->id,
            'logged_in_at' => now()->subHours(2),
            'last_activity' => now()->subHour(),
            'last_heart_beat' => now()->subHour(),
            'duration_in_seconds' => 3600,
        ]);
        UserLoginDuration::factory()->create([
            'user_id' => $firstUser->id,
            'logged_in_at' => now()->subHour(),
            'last_activity' => now(),
            'last_heart_beat' => now(),
            'duration_in_seconds' => null,
        ]);
        UserLoginDuration::factory()->create([
            'user_id' => $admin->id,
            'logged_in_at' => now()->subHour(),
            'last_activity' => now(),
            'last_heart_beat' => now(),
            'duration_in_seconds' => null,
        ]);

        $snapshot = resolve(AdminStatisticsDashboardService::class)->snapshot();

        $this->assertSame(1, $snapshot['login_participation_summary'][0]['distinct_login_users']);
        $this->assertSame(2, $snapshot['login_participation_summary'][0]['total_users']);
        $this->assertSame(50.0, $snapshot['login_participation_summary'][0]['percentage']);
        $this->assertStringContainsString('total non-admin user count', $snapshot['metric_definitions']['login_participation']);
    }

    public function test_login_participation_windows_include_required_ranges(): void
    {
        $this->createUser();

        $snapshot = resolve(AdminStatisticsDashboardService::class)->snapshot();

        $this->assertSame('today', $snapshot['login_participation_summary'][0]['window']);
        $this->assertSame('7_days', $snapshot['login_participation_summary'][1]['window']);
        $this->assertSame('14_days', $snapshot['login_participation_summary'][2]['window']);
        $this->assertSame('30_days', $snapshot['login_participation_summary'][3]['window']);
        $this->assertSame('6_months', $snapshot['login_participation_summary'][4]['window']);
        $this->assertSame('1_year', $snapshot['login_participation_summary'][5]['window']);
    }

    public function test_online_characters_are_based_on_open_login_duration_rows(): void
    {
        $onlineUser = $this->createUser(['email' => 'online@example.com']);
        $offlineUser = $this->createUser(['email' => 'offline@example.com']);
        $gameMap = GameMap::factory()->create(['name' => 'Surface']);
        $onlineCharacter = Character::factory()->create(['user_id' => $onlineUser->id, 'name' => 'Online Character', 'level' => 44]);
        Character::factory()->create(['user_id' => $offlineUser->id, 'name' => 'Offline Character', 'level' => 55]);
        Map::factory()->create(['character_id' => $onlineCharacter->id, 'game_map_id' => $gameMap->id]);
        UserLoginDuration::factory()->create([
            'user_id' => $onlineUser->id,
            'logged_in_at' => now()->subHour(),
            'last_activity' => now(),
            'last_heart_beat' => now(),
            'duration_in_seconds' => null,
        ]);
        UserLoginDuration::factory()->create([
            'user_id' => $offlineUser->id,
            'logged_in_at' => now()->subHours(2),
            'logged_out_at' => now()->subHour(),
            'last_activity' => now()->subHour(),
            'last_heart_beat' => now()->subHour(),
            'duration_in_seconds' => 3600,
        ]);

        $snapshot = resolve(AdminStatisticsDashboardService::class)->snapshot();

        $this->assertCount(1, $snapshot['online_characters']);
        $this->assertSame('Online Character', $snapshot['online_characters'][0]['character_name']);
        $this->assertSame('online@example.com', $snapshot['online_characters'][0]['user_name']);
        $this->assertSame('Surface', $snapshot['online_characters'][0]['map']);
    }

    public function test_online_characters_exclude_stale_open_login_rows_with_old_heartbeat(): void
    {
        $user = $this->createUser(['email' => 'stale@example.com']);
        Character::factory()->create(['user_id' => $user->id, 'name' => 'Stale Character']);
        UserLoginDuration::factory()->create([
            'user_id' => $user->id,
            'logged_in_at' => now()->subHours(2),
            'last_activity' => now()->subHour(),
            'last_heart_beat' => now()->subMinutes(31),
            'duration_in_seconds' => null,
        ]);

        $snapshot = resolve(AdminStatisticsDashboardService::class)->snapshot();

        $this->assertCount(0, $snapshot['online_characters']);
    }

    public function test_online_characters_include_recent_heartbeat_open_sessions(): void
    {
        $user = $this->createUser(['email' => 'recent@example.com']);
        Character::factory()->create(['user_id' => $user->id, 'name' => 'Recent Character']);
        UserLoginDuration::factory()->create([
            'user_id' => $user->id,
            'logged_in_at' => now()->subMinutes(10),
            'last_activity' => now()->subMinute(),
            'last_heart_beat' => now()->subMinute(),
            'duration_in_seconds' => null,
        ]);

        $snapshot = resolve(AdminStatisticsDashboardService::class)->snapshot();

        $this->assertCount(1, $snapshot['online_characters']);
        $this->assertSame('Recent Character', $snapshot['online_characters'][0]['character_name']);
    }

    public function test_today_login_count_chart_has_twenty_four_readable_hourly_points(): void
    {
        $user = $this->createUser();
        UserLoginDuration::factory()->create([
            'user_id' => $user->id,
            'logged_in_at' => now()->startOfDay()->addHours(2),
            'last_activity' => now()->startOfDay()->addHours(2),
            'last_heart_beat' => now()->startOfDay()->addHours(2),
            'duration_in_seconds' => 60,
        ]);

        $snapshot = resolve(AdminStatisticsDashboardService::class)->snapshot();

        $this->assertCount(24, $snapshot['today_login_count_chart']['points']);
        $this->assertSame('12 AM', $snapshot['today_login_count_chart']['points'][0]['label']);
        $this->assertSame('11 PM', $snapshot['today_login_count_chart']['points'][23]['label']);
    }

    public function test_login_activity_chart_includes_logins_and_inactive_users_deleted_series(): void
    {
        UserSiteAccessStatistics::factory()->create([
            'amount_signed_in' => 5,
            'created_at' => now()->startOfDay(),
        ]);
        InactiveUserDeletionStatistic::factory()->create([
            'deleted_count' => 2,
            'tracked_at' => now()->startOfDay(),
        ]);

        $snapshot = resolve(AdminStatisticsDashboardService::class)->snapshot();

        $this->assertSame('Logins', $snapshot['login_chart']['series'][0]['label']);
        $this->assertSame('Inactive users deleted', $snapshot['login_chart']['series'][1]['label']);
        $this->assertSame(2.0, $snapshot['login_chart']['series'][1]['points'][0]['value']);
    }

    public function test_inactive_user_deletion_statistics_are_recorded_when_delete_command_runs(): void
    {
        Queue::fake();
        $firstUser = $this->createUser(['will_be_deleted' => true]);
        $secondUser = $this->createUser(['will_be_deleted' => true]);
        Character::factory()->create(['user_id' => $firstUser->id, 'name' => 'Inactive Deleted One']);
        Character::factory()->create(['user_id' => $secondUser->id, 'name' => 'Inactive Deleted Two']);

        $this->artisan('delete:flagged-users');

        $this->assertSame(2, InactiveUserDeletionStatistic::first()->deleted_count);
        Queue::assertPushed(AccountDeletionJob::class, 2);
    }

    public function test_character_gold_stats_include_characters_without_kingdoms(): void
    {
        $ownerUser = $this->createUser();
        $noKingdomUser = $this->createUser();
        $gameMap = GameMap::factory()->create(['name' => 'Kingdom Map']);
        $owner = Character::factory()->create(['user_id' => $ownerUser->id, 'name' => 'Kingdom Owner', 'gold' => 10]);
        Character::factory()->create(['user_id' => $noKingdomUser->id, 'name' => 'No Kingdom Rich', 'gold' => 500]);
        Kingdom::factory()->create(['character_id' => $owner->id, 'game_map_id' => $gameMap->id]);

        $snapshot = resolve(AdminStatisticsDashboardService::class)->snapshot();

        $this->assertSame('No Kingdom Rich', $snapshot['gold_chart']['points'][0]['label']);
        $this->assertSame(500.0, $snapshot['gold_chart']['points'][0]['value']);
    }

    public function test_guide_quest_completion_stats_use_guide_quest_completion_source(): void
    {
        $user = $this->createUser();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $gameMap = GameMap::factory()->create(['name' => 'Quest Map']);
        $npc = Npc::factory()->create(['game_map_id' => $gameMap->id]);
        $quest = Quest::factory()->create(['name' => 'Regular Quest Source', 'npc_id' => $npc->id]);
        $guideQuest = GuideQuest::factory()->create(['name' => 'Guide Quest Source']);
        QuestsCompleted::factory()->create(['character_id' => $character->id, 'quest_id' => $quest->id, 'guide_quest_id' => null]);
        QuestsCompleted::factory()->create(['character_id' => $character->id, 'quest_id' => null, 'guide_quest_id' => $guideQuest->id]);

        $snapshot = resolve(AdminStatisticsDashboardService::class)->snapshot();

        $this->assertSame('Guide Quest Source', $snapshot['guide_quest_completion_chart']['points'][0]['label']);
        $this->assertSame(1.0, $snapshot['guide_quest_completion_chart']['points'][0]['value']);
        $this->assertSame('quests_completed.guide_quest_id', $snapshot['guide_quest_completion_chart']['source']);
    }
}
