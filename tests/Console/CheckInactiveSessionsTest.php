<?php

namespace Tests\Console;

use App\Flare\Models\User;
use App\Flare\Models\UserLoginDuration;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckInactiveSessionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_stale_open_session_closes_at_last_known_boundary(): void
    {
        Carbon::setTestNow('2026-07-19 12:00:00');
        $user = User::factory()->create();
        $session = UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->subHours(3), 'last_activity' => now()->subHour(), 'last_heart_beat' => now()->subHours(2)]);

        $this->artisan('check:inactive-sessions');

        $this->assertTrue($session->refresh()->logged_out_at->equalTo(now()->subHour()));
    }

    public function test_stale_session_does_not_close_at_command_time(): void
    {
        Carbon::setTestNow('2026-07-19 12:00:00');
        $user = User::factory()->create();
        $session = UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->subHours(3), 'last_activity' => now()->subHour(), 'last_heart_beat' => now()->subHour()]);

        $this->artisan('check:inactive-sessions');

        $this->assertFalse($session->refresh()->logged_out_at->equalTo(now()));
    }

    public function test_stale_session_duration_matches_persisted_end(): void
    {
        Carbon::setTestNow('2026-07-19 12:00:00');
        $user = User::factory()->create();
        $session = UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->subHours(3), 'last_activity' => now()->subHour(), 'last_heart_beat' => now()->subHour()]);

        $this->artisan('check:inactive-sessions');

        $session->refresh();
        $this->assertSame($session->logged_in_at->diffInSeconds($session->logged_out_at), $session->duration_in_seconds);
    }

    public function test_latest_valid_activity_boundary_is_used(): void
    {
        Carbon::setTestNow('2026-07-19 12:00:00');
        $user = User::factory()->create();
        $session = UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->subHours(4), 'last_activity' => now()->subHours(2), 'last_heart_beat' => now()->subHour()]);

        $this->artisan('check:inactive-sessions');

        $this->assertTrue($session->refresh()->logged_out_at->equalTo(now()->subHour()));
    }

    public function test_boundary_before_login_is_clamped_to_login(): void
    {
        Carbon::setTestNow('2026-07-19 12:00:00');
        $user = User::factory()->create();
        $session = UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->subHour(), 'last_activity' => now()->subHours(2), 'last_heart_beat' => now()->subHours(2)]);

        $this->artisan('check:inactive-sessions');

        $this->assertTrue($session->refresh()->logged_out_at->equalTo($session->logged_in_at));
    }

    public function test_non_stale_session_remains_open(): void
    {
        Carbon::setTestNow('2026-07-19 12:00:00');
        $user = User::factory()->create();
        $session = UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->subHour(), 'last_activity' => now()->subMinute(), 'last_heart_beat' => now()->subMinute()]);

        $this->artisan('check:inactive-sessions');

        $this->assertNull($session->refresh()->logged_out_at);
    }

    public function test_closed_session_remains_unchanged(): void
    {
        Carbon::setTestNow('2026-07-19 12:00:00');
        $user = User::factory()->create();
        $loggedOutAt = now()->subHour();
        $session = UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->subHours(2), 'logged_out_at' => $loggedOutAt, 'duration_in_seconds' => 3600, 'last_activity' => $loggedOutAt, 'last_heart_beat' => $loggedOutAt]);

        $this->artisan('check:inactive-sessions');

        $this->assertTrue($session->refresh()->logged_out_at->equalTo($loggedOutAt));
    }
}
