<?php

namespace Tests\Feature\Info\Monsters;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateMonster;

class MonstersApiControllerTest extends TestCase
{
    use CreateGameMap, CreateMonster, RefreshDatabase;

    public function test_guest_can_access_public_monster_detail(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Public Monster Map']);
        $monster = $this->createMonster(['name' => 'Public Monster', 'game_map_id' => $gameMap->id]);

        $response = $this->call('GET', "/api/information/monsters/{$monster->id}", [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(200);
        $data = json_decode($response->getContent(), true);

        $this->assertSame('Public Monster', $data['identity']['name']);
        $this->assertSame('Public Monster Map', $data['identity']['game_map']['name']);
    }
}
