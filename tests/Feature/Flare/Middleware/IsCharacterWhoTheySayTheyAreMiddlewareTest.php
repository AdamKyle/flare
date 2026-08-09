<?php

namespace Tests\Feature\Flare\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class IsCharacterWhoTheySayTheyAreMiddlewareTest extends TestCase
{
    use RefreshDatabase;

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
