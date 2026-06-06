<?php

namespace Tests\Feature\Game\Authentication;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class GameRouteAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function testGuestNonAjaxJsonRequestToInventoryReturns401(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->call('GET', '/api/character/' . $character->id . '/inventory', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testGuestAjaxRequestToInventoryReturns401(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->call('GET', '/api/character/' . $character->id . '/inventory', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ]);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testGuestBrowserRequestToInventoryIsBlocked(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->call('GET', '/api/character/' . $character->id . '/inventory');

        $this->assertNotEquals(200, $response->getStatusCode());
    }

    public function testAuthenticatedUserCannotAccessAnotherUsersInventory(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $otherUser = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter()->user;

        $response = $this->actingAs($otherUser)->call('GET', '/api/character/' . $character->id . '/inventory', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertEquals(422, $response->getStatusCode());
    }

    public function testOwnerCanAccessOwnInventory(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->actingAs($character->user)->call('GET', '/api/character/' . $character->id . '/inventory');

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGuestIsRejectedFromCharacterSheet(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->call('GET', '/api/character-sheet/' . $character->id, [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testGuestIsRejectedFromUpdateCharacterTimers(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->call('GET', '/api/update-character-timers/' . $character->id, [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testGuestIsRejectedFromFactionLoyalty(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->call('GET', '/api/faction-loyalty/' . $character->id, [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testGuestIsRejectedFromSocketedGems(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->call('GET', '/api/socketed-gems/' . $character->id . '/1', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testGuestIsRejectedFromLabyrinthOracle(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->call('GET', '/api/character/' . $character->id . '/labyrinth-oracle', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testGuestIsRejectedFromQueenOfHeartsUniques(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->call('GET', '/api/character/' . $character->id . '/inventory/uniques', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testGuestIsRejectedFromSeerCamp(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->call('GET', '/api/visit-seer-camp/' . $character->id, [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testGuestIsRejectedFromSmithsWorkbench(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->call('GET', '/api/character/' . $character->id . '/inventory/smiths-workbench', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testGuestIsRejectedFromSpecialtyShop(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->call('GET', '/api/specialty-shop/' . $character->id, [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testGuestIsRejectedFromGoblinShop(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->call('GET', '/goblin-shop/' . $character->id, [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertEquals(401, $response->getStatusCode());
    }
}
