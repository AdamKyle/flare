<?php

namespace Tests\Unit\Flare\Middleware;

use App\Flare\Middleware\TrackSessionLifeMiddleware;
use App\Flare\Models\User;
use App\Flare\Models\UserLoginDuration;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class TrackSessionLifeMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_request_updates_heartbeat_and_activity_time(): void
    {
        Carbon::setTestNow('2026-07-19 12:00:00');
        $user = User::factory()->create();
        $session = UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->subHour(), 'last_activity' => now()->subMinute(), 'last_heart_beat' => now()->subMinute()]);
        $this->actingAs($user);

        (new TrackSessionLifeMiddleware())->handle(Request::create('/game', 'GET'), fn () => response('ok'));

        $this->assertTrue($session->refresh()->last_activity->equalTo(now()));
        $this->assertTrue($session->last_heart_beat->equalTo(now()));
    }

    public function test_old_login_with_recent_activity_remains_authenticated(): void
    {
        Carbon::setTestNow('2026-07-19 12:00:00');
        config(['session.lifetime' => 30]);
        $user = User::factory()->create();
        UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->subDay(), 'last_activity' => now()->subMinute(), 'last_heart_beat' => now()->subMinute()]);
        $this->actingAs($user);

        (new TrackSessionLifeMiddleware())->handle(Request::create('/game', 'GET'), fn () => response('ok'));

        $this->assertTrue(Auth::check());
    }

    public function test_expired_session_closes_at_latest_activity_boundary(): void
    {
        Carbon::setTestNow('2026-07-19 12:00:00');
        config(['session.lifetime' => 30]);
        $user = User::factory()->create();
        $session = UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->subHours(3), 'last_activity' => now()->subHour(), 'last_heart_beat' => now()->subHours(2)]);
        $this->actingAs($user);

        (new TrackSessionLifeMiddleware())->handle(Request::create('/game', 'GET'), fn () => response('ok'));

        $this->assertTrue($session->refresh()->logged_out_at->equalTo(now()->subHour()));
    }

    public function test_expired_session_is_not_extended_to_request_time(): void
    {
        Carbon::setTestNow('2026-07-19 12:00:00');
        config(['session.lifetime' => 30]);
        $user = User::factory()->create();
        $session = UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->subHours(3), 'last_activity' => now()->subHour(), 'last_heart_beat' => now()->subHour()]);
        $this->actingAs($user);

        (new TrackSessionLifeMiddleware())->handle(Request::create('/game', 'GET'), fn () => response('ok'));

        $this->assertFalse($session->refresh()->logged_out_at->equalTo(now()));
    }

    public function test_expired_session_duration_matches_persisted_end(): void
    {
        Carbon::setTestNow('2026-07-19 12:00:00');
        config(['session.lifetime' => 30]);
        $user = User::factory()->create();
        $session = UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->subHours(3), 'last_activity' => now()->subHour(), 'last_heart_beat' => now()->subHour()]);
        $this->actingAs($user);

        (new TrackSessionLifeMiddleware())->handle(Request::create('/game', 'GET'), fn () => response('ok'));

        $session->refresh();
        $this->assertSame($session->logged_in_at->diffInSeconds($session->logged_out_at), $session->duration_in_seconds);
    }

    public function test_expired_session_end_cannot_precede_login(): void
    {
        Carbon::setTestNow('2026-07-19 12:00:00');
        config(['session.lifetime' => 30]);
        $user = User::factory()->create();
        $session = UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->subHour(), 'last_activity' => now()->subHours(2), 'last_heart_beat' => now()->subHours(2)]);
        $this->actingAs($user);

        (new TrackSessionLifeMiddleware())->handle(Request::create('/game', 'GET'), fn () => response('ok'));

        $this->assertTrue($session->refresh()->logged_out_at->equalTo($session->logged_in_at));
    }

    public function test_no_open_session_exits_without_creating_a_record(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        (new TrackSessionLifeMiddleware())->handle(Request::create('/game', 'GET'), fn () => response('ok'));

        $this->assertSame(0, UserLoginDuration::where('user_id', $user->id)->count());
    }

    public function test_closed_session_is_not_rewritten(): void
    {
        Carbon::setTestNow('2026-07-19 12:00:00');
        $user = User::factory()->create();
        $loggedOutAt = now()->subHour();
        $session = UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->subHours(2), 'logged_out_at' => $loggedOutAt, 'duration_in_seconds' => 3600, 'last_activity' => $loggedOutAt, 'last_heart_beat' => $loggedOutAt]);
        $this->actingAs($user);

        (new TrackSessionLifeMiddleware())->handle(Request::create('/game', 'GET'), fn () => response('ok'));

        $this->assertTrue($session->refresh()->logged_out_at->equalTo($loggedOutAt));
    }
}
