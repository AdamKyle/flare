<?php

namespace Tests\Feature\Info\Quests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;

class QuestsApiControllerTest extends TestCase
{
    use CreateGameMap, CreateNpc, CreateQuest, RefreshDatabase;

    public function test_guest_can_access_public_quest_tree(): void
    {
        $this->createQuest(['name' => 'Public Quest']);

        $response = $this->call('GET', '/api/information/quests/tree', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(200);
        $this->assertSame('Public Quest', json_decode($response->getContent(), true)['quests'][0]['name']);
    }

    public function test_public_quest_tree_can_be_filtered_by_map(): void
    {
        $mapOne = $this->createGameMap(['name' => 'Map One']);
        $mapTwo = $this->createGameMap(['name' => 'Map Two']);
        $npcOne = $this->createNpc(['game_map_id' => $mapOne->id, 'real_name' => 'Giver One']);
        $npcTwo = $this->createNpc(['game_map_id' => $mapTwo->id, 'real_name' => 'Giver Two']);

        $this->createQuest(['name' => 'On Map One', 'npc_id' => $npcOne->id]);
        $this->createQuest(['name' => 'On Map Two', 'npc_id' => $npcTwo->id]);

        $response = $this->call('GET', '/api/information/quests/tree', ['map_id' => $mapOne->id], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $data = json_decode($response->getContent(), true)['quests'];

        $this->assertCount(1, $data);
        $this->assertSame('On Map One', $data[0]['name']);
    }

    public function test_public_quest_tree_can_be_filtered_by_kind(): void
    {
        $this->createQuest(['name' => 'A One Off']);
        $this->createQuest(['name' => 'B Chain Root', 'is_parent' => true]);

        $response = $this->call('GET', '/api/information/quests/tree', ['kind' => 'chain'], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $data = json_decode($response->getContent(), true)['quests'];

        $this->assertCount(1, $data);
        $this->assertSame('B Chain Root', $data[0]['name']);
    }

    public function test_guest_can_access_public_quest_detail(): void
    {
        $quest = $this->createQuest(['name' => 'Detail Quest']);

        $response = $this->call('GET', "/api/information/quests/{$quest->id}", [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(200);
        $this->assertSame('Detail Quest', json_decode($response->getContent(), true)['name']);
    }
}
