<?php

namespace Tests\Unit\Flare\Listeners;

use App\Flare\Listeners\RecordUserLoginTimeListener;
use App\Flare\Models\UserLoginDuration;
use Carbon\Carbon;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateUserLoginDuration;

class RecordUserLoginTimeListenerTest extends TestCase
{
    use CreateRole, CreateUser, CreateUserLoginDuration, RefreshDatabase;

    public function test_does_nothing_for_an_admin_user(): void
    {
        $this->createAdminRole();
        $user = $this->createUser();
        $user->assignRole('Admin');

        (new RecordUserLoginTimeListener())->handle(new Login('web', $user, false));

        $this->assertSame(0, UserLoginDuration::where('user_id', $user->id)->count());
    }

    public function test_creates_a_new_open_session_for_a_regular_user(): void
    {
        $user = $this->createUser();

        (new RecordUserLoginTimeListener())->handle(new Login('web', $user, false));

        $this->assertSame(1, UserLoginDuration::where('user_id', $user->id)->whereNull('logged_out_at')->count());
    }

    public function test_closes_an_already_open_session_before_opening_a_new_one(): void
    {
        $user = $this->createUser();
        $openSession = $this->createUserLoginDuration([
            'user_id' => $user->id,
            'logged_in_at' => now()->subHour(),
            'last_heart_beat' => now()->subMinutes(30),
            'last_activity' => now()->subMinutes(30),
            'logged_out_at' => null,
            'duration_in_seconds' => null,
        ]);

        (new RecordUserLoginTimeListener())->handle(new Login('web', $user, false));

        $openSession->refresh();
        $this->assertNotNull($openSession->logged_out_at);
        $this->assertNotNull($openSession->duration_in_seconds);
        $this->assertSame(2, UserLoginDuration::where('user_id', $user->id)->count());
    }

    public function test_clamps_the_closed_sessions_end_to_its_login_time_when_the_heartbeat_precedes_it(): void
    {
        Carbon::setTestNow('2026-07-19 12:00:00');

        $user = $this->createUser();
        $openSession = $this->createUserLoginDuration([
            'user_id' => $user->id,
            'logged_in_at' => now()->addMinute(),
            'last_heart_beat' => now(),
            'last_activity' => now(),
            'logged_out_at' => null,
            'duration_in_seconds' => null,
        ]);

        (new RecordUserLoginTimeListener())->handle(new Login('web', $user, false));

        $openSession->refresh();
        $this->assertTrue($openSession->logged_out_at->equalTo($openSession->logged_in_at));
        $this->assertSame(0, $openSession->duration_in_seconds);
    }
}
