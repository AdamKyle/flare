<?php

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PreventRequestForgeryTest extends TestCase
{
    public function test_request_succeeds_when_session_and_submitted_tokens_match(): void
    {
        Route::post('/test-routes/prevent-request-forgery/matching-token', fn () => response('ok'))->middleware('web');

        $originalEnvironment = $this->app['env'];
        $this->app['env'] = 'production';

        $token = 'a-matching-csrf-token-value';

        $response = $this->withSession(['_token' => $token])
            ->post('/test-routes/prevent-request-forgery/matching-token', ['_token' => $token]);

        $this->app['env'] = $originalEnvironment;

        $response->assertOk();
    }

    public function test_request_fails_with_419_when_no_token_is_supplied(): void
    {
        Route::post('/test-routes/prevent-request-forgery/no-token', fn () => response('ok'))->middleware('web');

        $originalEnvironment = $this->app['env'];
        $this->app['env'] = 'production';

        $response = $this->withSession(['_token' => 'a-real-session-token'])
            ->post('/test-routes/prevent-request-forgery/no-token');

        $this->app['env'] = $originalEnvironment;

        $response->assertStatus(302);
        $response->assertRedirect('/');
        $response->assertSessionHas('error', 'You were logged out due to inactivity. Please login again.');
    }

    public function test_request_fails_with_419_when_invalid_token_is_supplied(): void
    {
        Route::post('/test-routes/prevent-request-forgery/invalid-token', fn () => response('ok'))->middleware('web');

        $originalEnvironment = $this->app['env'];
        $this->app['env'] = 'production';

        $response = $this->withSession(['_token' => 'a-real-session-token'])
            ->post('/test-routes/prevent-request-forgery/invalid-token', ['_token' => 'a-different-token']);

        $this->app['env'] = $originalEnvironment;

        $response->assertStatus(302);
        $response->assertRedirect('/');
        $response->assertSessionHas('error', 'You were logged out due to inactivity. Please login again.');
    }

    public function test_request_succeeds_with_same_origin_sec_fetch_site_header(): void
    {
        Route::post('/test-routes/prevent-request-forgery/same-origin', fn () => response('ok'))->middleware('web');

        $originalEnvironment = $this->app['env'];
        $this->app['env'] = 'production';

        $response = $this->withHeaders(['Sec-Fetch-Site' => 'same-origin'])
            ->post('/test-routes/prevent-request-forgery/same-origin');

        $this->app['env'] = $originalEnvironment;

        $response->assertOk();
    }

    public function test_request_fails_with_cross_site_sec_fetch_site_header(): void
    {
        Route::post('/test-routes/prevent-request-forgery/cross-site', fn () => response('ok'))->middleware('web');

        $originalEnvironment = $this->app['env'];
        $this->app['env'] = 'production';

        $response = $this->withHeaders(['Sec-Fetch-Site' => 'cross-site'])
            ->post('/test-routes/prevent-request-forgery/cross-site');

        $this->app['env'] = $originalEnvironment;

        $response->assertStatus(302);
        $response->assertRedirect('/');
        $response->assertSessionHas('error', 'You were logged out due to inactivity. Please login again.');
    }

    public function test_excluded_uri_bypasses_request_forgery_verification(): void
    {
        Route::post('/test-routes/prevent-request-forgery/excluded', fn () => response('ok'))->middleware('web');

        PreventRequestForgery::except(['test-routes/prevent-request-forgery/excluded']);

        $originalEnvironment = $this->app['env'];
        $this->app['env'] = 'production';

        $response = $this->post('/test-routes/prevent-request-forgery/excluded');

        $this->app['env'] = $originalEnvironment;
        PreventRequestForgery::flushState();

        $response->assertOk();
    }
}
