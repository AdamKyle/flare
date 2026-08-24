<?php

namespace Tests\Unit\Flare\Middleware;

use App\Flare\Middleware\UpdatePlayerSessionActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateUserLoginDuration;

class UpdatePlayerSessionActivityTest extends TestCase
{
    use CreateUser, CreateUserLoginDuration, RefreshDatabase;

    public function test_guest_request_passes_through(): void
    {
        $response = (new UpdatePlayerSessionActivity())->handle(Request::create('/'), fn ($request) => response('ok'));

        $this->assertSame('ok', $response->getContent());
    }

    public function test_authenticated_user_without_open_session_passes_through(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $response = (new UpdatePlayerSessionActivity())->handle(Request::create('/'), fn ($request) => response('ok'));

        $this->assertSame('ok', $response->getContent());
    }

    public function test_authenticated_user_with_open_session_updates_last_activity(): void
    {
        $user = $this->createUser();
        $session = $this->createUserLoginDuration([
            'user_id' => $user->id,
            'logged_in_at' => now()->subHour(),
            'last_activity' => now()->subMinutes(20),
            'last_heart_beat' => now()->subMinutes(20),
            'duration_in_seconds' => null,
        ]);
        $this->actingAs($user);

        (new UpdatePlayerSessionActivity())->handle(Request::create('/'), fn ($request) => response('ok'));

        $this->assertTrue($session->refresh()->last_activity->greaterThan(now()->subMinute()));
    }
}
