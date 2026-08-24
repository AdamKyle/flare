<?php

namespace Tests\Unit\Flare\Middleware;

use App\Flare\Middleware\IsGloballyTimedOut;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class IsGloballyTimedOutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(IsGloballyTimedOut::class)->get('/__test-timeout-json', fn () => response('ok'))->name('__test-timeout-json');
        Route::middleware(IsGloballyTimedOut::class)->get('/__test-timeout-web', fn () => response('ok'))->name('__test-timeout-web');
    }

    public function test_user_without_timeout_passes_through(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $this->actingAs($character->user);

        $response = $this->get('/__test-timeout-web');

        $response->assertOk();
        $response->assertSee('ok');
    }

    public function test_timed_out_user_json_request_receives_422(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $character->user->update(['timeout_until' => now()->addMinutes(5)]);
        $this->actingAs($character->user);

        $response = $this->getJson('/__test-timeout-json');

        $response->assertStatus(422);
    }

    public function test_timed_out_user_web_request_redirects_to_game(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $character->user->update(['timeout_until' => now()->addMinutes(5)]);
        $this->actingAs($character->user);

        $response = $this->get('/__test-timeout-web');

        $response->assertRedirect(route('game'));
    }
}
