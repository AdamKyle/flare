<?php

namespace Tests\Feature\Game\Kingdoms\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateKingdom;

class KingdomInformationTest extends TestCase
{
    use CreateGameMap, CreateKingdom, RefreshDatabase;

    public function test_character_can_view_enemy_kingdom_on_current_map(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $enemy = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $kingdom = $this->createKingdom([
            'character_id' => $enemy->id,
            'game_map_id' => $character->map->game_map_id,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/kingdom/'.$kingdom->id.'/'.$character->id);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($kingdom->id, $responseData['id']);
    }

    public function test_character_can_view_npc_kingdom_on_current_map(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $kingdom = $this->createKingdom([
            'character_id' => null,
            'game_map_id' => $character->map->game_map_id,
            'npc_owned' => true,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/kingdom/'.$kingdom->id.'/'.$character->id);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($kingdom->id, $responseData['id']);
    }

    public function test_character_cannot_view_kingdom_from_another_map(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $otherMap = $this->createGameMap(['name' => 'Other Map']);
        $kingdom = $this->createKingdom([
            'character_id' => $otherCharacter->id,
            'game_map_id' => $otherMap->id,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/kingdom/'.$kingdom->id.'/'.$character->id);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('Kingdom not found on this map.', $responseData['message']);
    }

    public function test_owner_only_kingdom_details_remain_protected(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $enemy = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $kingdom = $this->createKingdom([
            'character_id' => $enemy->id,
            'game_map_id' => $character->map->game_map_id,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/player-kingdom/'.$character->id.'/'.$kingdom->id, [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('Nope. Not allowed to do that.', $responseData['error']);
    }
}
