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

    public function testLatestOpenSessionIsClosed(): void
    {
        $user = User::factory()->create();
        UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->subHours(3), 'last_activity' => now()->subHours(2), 'last_heart_beat' => now()->subHours(2)]);
        $latest = UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->subHour(), 'last_activity' => now(), 'last_heart_beat' => now()]);

        (new RecordUserLogOutTimeListener())->handle(new Logout('web', $user));

        $this->assertNotNull($latest->refresh()->logged_out_at);
    }

    public function testAlreadyClosedSessionIsNotRewritten(): void
    {
        $user = User::factory()->create();
        $session = UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->subHours(2), 'logged_out_at' => now()->subHour(), 'last_activity' => now()->subHour(), 'last_heart_beat' => now()->subHour(), 'duration_in_seconds' => 3600]);

        (new RecordUserLogOutTimeListener())->handle(new Logout('web', $user));

        $this->assertSame(3600, $session->refresh()->duration_in_seconds);
    }

    public function testNoOpenSessionReturnsWithoutMutation(): void
    {
        $user = User::factory()->create();
        $loggedOutAt = now()->subHour();
        $session = UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->subHours(2), 'logged_out_at' => $loggedOutAt, 'last_activity' => $loggedOutAt, 'last_heart_beat' => $loggedOutAt, 'duration_in_seconds' => 3600]);

        (new RecordUserLogOutTimeListener())->handle(new Logout('web', $user));

        $this->assertSame($loggedOutAt->format('Y-m-d H:i:s'), $session->refresh()->logged_out_at->format('Y-m-d H:i:s'));
    }

    public function testLogoutTimeCannotCreateNegativeDuration(): void
    {
        Carbon::setTestNow('2026-07-18 12:00:00');
        $user = User::factory()->create();
        $session = UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->addHour(), 'last_activity' => now(), 'last_heart_beat' => now()]);

        (new RecordUserLogOutTimeListener())->handle(new Logout('web', $user));

        $this->assertSame(0, $session->refresh()->duration_in_seconds);
    }

    public function testNullUserReturnsWithoutMutation(): void
    {
        $user = User::factory()->create();
        $session = UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->subHour(), 'last_activity' => now(), 'last_heart_beat' => now()]);

        (new RecordUserLogOutTimeListener())->handle(new Logout('web', null));

        $this->assertNull($session->refresh()->logged_out_at);
    }

    public function testAdminLogoutDoesNotAlterLoginDurationRecords(): void
    {
        Role::create(['name' => 'Admin']);
        $user = User::factory()->create();
        $user->assignRole('Admin');
        $session = UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->subHour(), 'last_activity' => now(), 'last_heart_beat' => now()]);

        (new RecordUserLogOutTimeListener())->handle(new Logout('web', $user));

        $this->assertNull($session->refresh()->logged_out_at);
    }
}
