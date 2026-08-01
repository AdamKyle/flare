<?php

namespace Tests\Feature\Game\Tops;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateKingdom;
use Tests\Traits\CreateUser;

class KingdomTopsApiTest extends TestCase
{
    use CreateCharacter, CreateGameMap, CreateKingdom, CreateUser, RefreshDatabase;

    public function test_kingdom_tops_excludes_npc_owned_kingdoms_by_default(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'name' => 'Ruler']);
        $map = $this->createGameMap();
        $this->createKingdom(['character_id' => $character->id, 'game_map_id' => $map->id, 'npc_owned' => false, 'treasury' => 100]);
        $this->createKingdom(['character_id' => $character->id, 'game_map_id' => $map->id, 'npc_owned' => true, 'treasury' => 999999]);

        $response = $this->actingAs($user)->call('GET', '/api/game/tops/kingdoms');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame(1, $data['rows'][0]['kingdom_count']);
        $this->assertSame('/game/tops/characters/'.$character->id, $data['rows'][0]['character_profile_url']);
        $this->assertStringNotContainsString($user->email, $response->getContent());
    }
}
