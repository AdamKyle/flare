<?php

namespace Tests\Unit\Flare\Middleware;

use App\Flare\Middleware\IsPlayerBannedMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class IsPlayerBannedMiddlewareTest extends TestCase
{
    use CreateUser, RefreshDatabase;

    public function test_guest_request_passes_through(): void
    {
        $response = (new IsPlayerBannedMiddleware())->handle(Request::create('/'), fn ($request) => response('ok'));

        $this->assertSame('ok', $response->getContent());
    }

    public function test_non_banned_user_passes_through(): void
    {
        $user = $this->createUser(['is_banned' => false]);
        $this->actingAs($user);

        $response = (new IsPlayerBannedMiddleware())->handle(Request::create('/'), fn ($request) => response('ok'));

        $this->assertSame('ok', $response->getContent());
    }

    public function test_banned_user_is_logged_out_and_redirected(): void
    {
        $user = $this->createUser(['is_banned' => true, 'unbanned_at' => null]);
        $this->actingAs($user);

        $response = (new IsPlayerBannedMiddleware())->handle(Request::create('/'), fn ($request) => response('ok'));

        $this->assertTrue($response->isRedirect(url('/')));
        $this->assertFalse(Auth::check());
    }

    public function test_banned_user_with_unban_date_shows_it_in_the_message(): void
    {
        $user = $this->createUser(['is_banned' => true, 'unbanned_at' => now()->addWeek()]);
        $this->actingAs($user);

        $response = (new IsPlayerBannedMiddleware())->handle(Request::create('/'), fn ($request) => response('ok'));

        $this->assertStringContainsString('You have been banned until:', $response->getSession()->get('error'));
    }
}
