<?php

namespace Tests\Feature\Game\Authentication;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class GameRouteAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_non_ajax_json_request_to_inventory_returns401(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->call('GET', '/api/character/'.$character->id.'/inventory', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_guest_ajax_request_to_inventory_returns401(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->call('GET', '/api/character/'.$character->id.'/inventory', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ]);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_guest_browser_request_to_inventory_is_blocked(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->call('GET', '/api/character/'.$character->id.'/inventory');

        $this->assertNotEquals(200, $response->getStatusCode());
    }

    public function test_authenticated_user_cannot_access_another_users_inventory(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $otherUser = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter()->user;

        $response = $this->actingAs($otherUser)->call('GET', '/api/character/'.$character->id.'/inventory', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertEquals(422, $response->getStatusCode());
    }

    public function test_owner_can_access_own_inventory(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->actingAs($character->user)->call('GET', '/api/character/'.$character->id.'/inventory');

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_guest_is_rejected_from_character_sheet(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->call('GET', '/api/character-sheet/'.$character->id, [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_guest_is_rejected_from_update_character_timers(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->call('GET', '/api/update-character-timers/'.$character->id, [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_guest_is_rejected_from_faction_loyalty(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->call('GET', '/api/faction-loyalty/'.$character->id, [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_guest_is_rejected_from_socketed_gems(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->call('GET', '/api/socketed-gems/'.$character->id.'/1', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_guest_is_rejected_from_labyrinth_oracle(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->call('GET', '/api/character/'.$character->id.'/labyrinth-oracle', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_guest_is_rejected_from_queen_of_hearts_uniques(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->call('GET', '/api/character/'.$character->id.'/inventory/uniques', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_guest_is_rejected_from_seer_camp(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->call('GET', '/api/visit-seer-camp/'.$character->id, [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_guest_is_rejected_from_smiths_workbench(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->call('GET', '/api/character/'.$character->id.'/inventory/smiths-workbench', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_guest_is_rejected_from_specialty_shop(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->call('GET', '/api/specialty-shop/'.$character->id, [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_guest_is_rejected_from_goblin_shop(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->call('GET', '/goblin-shop/'.$character->id, [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_guest_json_access_to_event_calendar_is_blocked(): void
    {
        $response = $this->call('GET', '/game/event-calendar', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertEquals(401, $response->getStatusCode());
    }
}
