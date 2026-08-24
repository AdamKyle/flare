<?php

namespace Tests\Feature\Flare\Middleware;

use App\Flare\Middleware\IsCharacterWhoTheySayTheyAreMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class IsCharacterWhoTheySayTheyAreMiddlewareTest extends TestCase
{
    use CreateRole, CreateUser, RefreshDatabase;

    public function test_unauthenticated_requests_are_redirected_to_the_game_route(): void
    {
        $response = (new IsCharacterWhoTheySayTheyAreMiddleware())->handle(
            Request::create('/game', 'GET'),
            fn () => response('ok'),
        );

        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString(route('game'), $response->headers->get('Location'));
    }

    public function test_admin_is_redirected_home_when_the_route_is_not_on_the_allow_list(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->createAdminRole();
        $character->user->assignRole('Admin');

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/map/'.$character->id);

        $response->assertRedirect(route('home'));
    }

    public function test_admin_is_let_through_on_an_allow_listed_route_name(): void
    {
        Route::middleware(['web', 'auth', 'is.character.who.they.say.they.are'])
            ->get('/test-admin-allow-list-route', fn () => response('ok'))
            ->name('game.inventory.compare');

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->createAdminRole();
        $character->user->assignRole('Admin');

        $response = $this->actingAs($character->user)
            ->get('/test-admin-allow-list-route');

        $response->assertOk();
    }

    public function test_non_json_request_is_redirected_when_the_route_user_does_not_match(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $otherUser = $this->createUser();

        $response = $this->actingAs($character->user)
            ->get('/settings/'.$otherUser->id);

        $response->assertRedirect(route('game'));
    }

    public function test_allows_request_when_scalar_character_route_id_belongs_to_the_authenticated_user(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/map/'.$character->id);

        $response->assertOk();
    }

    public function test_blocks_request_when_scalar_character_route_id_does_not_belong_to_the_authenticated_user(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->actingAs($character->user)
            ->getJson('/api/map/'.$otherCharacter->id);

        $response->assertStatus(422);
        $this->assertSame('You don\'t have permission to do that.', json_decode($response->getContent(), true)['error']);
    }
}
