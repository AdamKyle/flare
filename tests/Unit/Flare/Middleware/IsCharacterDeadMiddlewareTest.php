<?php

namespace Tests\Unit\Flare\Middleware;

use App\Flare\Middleware\IsCharacterDeadMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class IsCharacterDeadMiddlewareTest extends TestCase
{
    use CreateRole, CreateUser, RefreshDatabase;

    public function test_admin_user_always_passes_through(): void
    {
        $this->createAdminRole();
        $admin = $this->createUser();
        $admin->assignRole('Admin');
        $this->actingAs($admin);

        $response = (new IsCharacterDeadMiddleware())->handle(Request::create('/game'), fn ($request) => response('ok'));

        $this->assertSame('ok', $response->getContent());
    }

    public function test_dead_character_json_request_receives_422(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $character->update(['is_dead' => true]);
        $this->actingAs($character->user);

        $request = Request::create('/game', 'GET', server: ['HTTP_ACCEPT' => 'application/json']);

        $response = (new IsCharacterDeadMiddleware())->handle($request, fn ($request) => response('ok'));

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_dead_character_web_request_redirects_to_game(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $character->update(['is_dead' => true]);
        $this->actingAs($character->user);

        $response = (new IsCharacterDeadMiddleware())->handle(Request::create('/game'), fn ($request) => response('ok'));

        $this->assertTrue($response->isRedirect());
    }

    public function test_alive_character_passes_through(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $character->update(['is_dead' => false]);
        $this->actingAs($character->user);

        $response = (new IsCharacterDeadMiddleware())->handle(Request::create('/game'), fn ($request) => response('ok'));

        $this->assertSame('ok', $response->getContent());
    }
}
