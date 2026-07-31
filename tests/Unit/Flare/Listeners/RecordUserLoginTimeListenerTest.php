<?php

namespace Tests\Unit\Flare\Listeners;

use App\Flare\Listeners\RecordUserLoginTimeListener;
use App\Flare\Models\Role;
use App\Flare\Models\User;
use App\Flare\Models\UserLoginDuration;
use Carbon\Carbon;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordUserLoginTimeListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_login_creates_one_open_session(): void
    {
        $user = User::factory()->create();

        (new RecordUserLoginTimeListener())->handle(new Login('web', $user, false));

        $this->assertSame(1, UserLoginDuration::where('user_id', $user->id)->whereNull('logged_out_at')->whereNull('duration_in_seconds')->count());
    }

    public function test_existing_open_session_is_closed_before_new_session_is_created(): void
    {
        Carbon::setTestNow('2026-07-18 12:00:00');
        $user = User::factory()->create();
        UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->subHours(2), 'last_activity' => now()->subHour(), 'last_heart_beat' => now()->subHour()]);

        (new RecordUserLoginTimeListener())->handle(new Login('web', $user, false));

        $this->assertSame(1, UserLoginDuration::where('user_id', $user->id)->whereNotNull('logged_out_at')->count());
    }

    public function test_existing_open_session_closes_at_its_last_heartbeat(): void
    {
        Carbon::setTestNow('2026-07-18 12:00:00');
        $user = User::factory()->create();
        $session = UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->subHours(2), 'last_activity' => now()->subHour(), 'last_heart_beat' => now()->subHour()]);

        (new RecordUserLoginTimeListener())->handle(new Login('web', $user, false));

        $this->assertTrue($session->refresh()->logged_out_at->equalTo(now()->subHour()));
    }

    public function test_heartbeat_before_login_cannot_produce_negative_duration(): void
    {
        Carbon::setTestNow('2026-07-18 12:00:00');
        $user = User::factory()->create();
        $session = UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->addHour(), 'last_activity' => now(), 'last_heart_beat' => now()]);

        (new RecordUserLoginTimeListener())->handle(new Login('web', $user, false));

        $this->assertSame(0, $session->refresh()->duration_in_seconds);
    }

    public function test_already_closed_session_is_not_rewritten(): void
    {
        $user = User::factory()->create();
        $session = UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->subHours(3), 'logged_out_at' => now()->subHours(2), 'last_activity' => now()->subHours(2), 'last_heart_beat' => now()->subHours(2), 'duration_in_seconds' => 3600]);

        (new RecordUserLoginTimeListener())->handle(new Login('web', $user, false));

        $this->assertSame(3600, $session->refresh()->duration_in_seconds);
    }

    public function test_admin_login_does_not_create_session(): void
    {
        Role::create(['name' => 'Admin']);
        $user = User::factory()->create();
        $user->assignRole('Admin');

        (new RecordUserLoginTimeListener())->handle(new Login('web', $user, false));

        $this->assertSame(0, UserLoginDuration::where('user_id', $user->id)->count());
    }
}
