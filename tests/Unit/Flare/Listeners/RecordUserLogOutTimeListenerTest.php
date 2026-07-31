<?php

namespace Tests\Unit\Flare\Listeners;

use App\Flare\Listeners\RecordUserLogOutTimeListener;
use App\Flare\Models\Role;
use App\Flare\Models\User;
use App\Flare\Models\UserLoginDuration;
use Carbon\Carbon;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordUserLogOutTimeListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_latest_open_session_is_closed(): void
    {
        $user = User::factory()->create();
        UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->subHours(3), 'last_activity' => now()->subHours(2), 'last_heart_beat' => now()->subHours(2)]);
        $latest = UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->subHour(), 'last_activity' => now(), 'last_heart_beat' => now()]);

        (new RecordUserLogOutTimeListener())->handle(new Logout('web', $user));

        $this->assertNotNull($latest->refresh()->logged_out_at);
    }

    public function test_already_closed_session_is_not_rewritten(): void
    {
        $user = User::factory()->create();
        $session = UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->subHours(2), 'logged_out_at' => now()->subHour(), 'last_activity' => now()->subHour(), 'last_heart_beat' => now()->subHour(), 'duration_in_seconds' => 3600]);

        (new RecordUserLogOutTimeListener())->handle(new Logout('web', $user));

        $this->assertSame(3600, $session->refresh()->duration_in_seconds);
    }

    public function test_no_open_session_returns_without_mutation(): void
    {
        $user = User::factory()->create();
        $loggedOutAt = now()->subHour();
        $session = UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->subHours(2), 'logged_out_at' => $loggedOutAt, 'last_activity' => $loggedOutAt, 'last_heart_beat' => $loggedOutAt, 'duration_in_seconds' => 3600]);

        (new RecordUserLogOutTimeListener())->handle(new Logout('web', $user));

        $this->assertSame($loggedOutAt->format('Y-m-d H:i:s'), $session->refresh()->logged_out_at->format('Y-m-d H:i:s'));
    }

    public function test_logout_time_cannot_create_negative_duration(): void
    {
        Carbon::setTestNow('2026-07-18 12:00:00');
        $user = User::factory()->create();
        $session = UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->addHour(), 'last_activity' => now(), 'last_heart_beat' => now()]);

        (new RecordUserLogOutTimeListener())->handle(new Logout('web', $user));

        $this->assertSame(0, $session->refresh()->duration_in_seconds);
    }

    public function test_null_user_returns_without_mutation(): void
    {
        $user = User::factory()->create();
        $session = UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->subHour(), 'last_activity' => now(), 'last_heart_beat' => now()]);

        (new RecordUserLogOutTimeListener())->handle(new Logout('web', null));

        $this->assertNull($session->refresh()->logged_out_at);
    }

    public function test_admin_logout_does_not_alter_login_duration_records(): void
    {
        Role::create(['name' => 'Admin']);
        $user = User::factory()->create();
        $user->assignRole('Admin');
        $session = UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->subHour(), 'last_activity' => now(), 'last_heart_beat' => now()]);

        (new RecordUserLogOutTimeListener())->handle(new Logout('web', $user));

        $this->assertNull($session->refresh()->logged_out_at);
    }
}
