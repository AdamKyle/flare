<?php

namespace Tests\Feature\Admin\Npcs;

use App\Game\Npcs\Values\NpcType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class NpcsStandaloneApiControllerTest extends TestCase
{
    use CreateGameMap, CreateItem, CreateNpc, CreateQuest, CreateRole, CreateUser, RefreshDatabase;

    public function test_index_rejects_unauthenticated_request(): void
    {
        $response = $this->call('GET', '/api/admin/npcs', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_index_rejects_non_admin_request(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/api/admin/npcs', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_index_returns_paginated_npcs(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap(['name' => 'Surface']);
        $this->createNpc(['game_map_id' => $gameMap->id, 'real_name' => 'Merchant Bob']);
        $this->createNpc(['game_map_id' => $gameMap->id, 'real_name' => 'Guard Alice']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/npcs');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(2, $data['data']);
        $this->assertSame(['id', 'real_name', 'type', 'map_name', 'x_position', 'y_position'], array_keys($data['data'][0]));
    }

    public function test_index_search_filters_by_real_name(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $this->createNpc(['game_map_id' => $gameMap->id, 'real_name' => 'Merchant Bob']);
        $this->createNpc(['game_map_id' => $gameMap->id, 'real_name' => 'Guard Alice']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/npcs?search_text=Merchant');
        $data = json_decode($response->getContent(), true);

        $this->assertCount(1, $data['data']);
        $this->assertSame('Merchant Bob', $data['data'][0]['real_name']);
    }

    public function test_show_npc_returns_exact_detail_shape(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap(['name' => 'Surface']);
        $npc = $this->createNpc([
            'game_map_id' => $gameMap->id,
            'real_name' => 'Merchant Bob',
            'type' => NpcType::QUEST_GIVER->value,
            'x_position' => 10,
            'y_position' => 20,
        ]);
        $rewardItem = $this->createItem(['name' => 'Reward Sword']);
        $this->createQuest(['npc_id' => $npc->id, 'name' => 'Fetch Quest', 'reward_item' => $rewardItem->id]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/npcs/'.$npc->id);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($npc->id, $data['id']);
        $this->assertSame(['id' => $gameMap->id, 'name' => 'Surface'], $data['game_map']);
        $this->assertSame('Merchant Bob', $data['real_name']);
        $this->assertSame(NpcType::QUEST_GIVER->value, $data['type']);
        $this->assertSame(10, $data['x_position']);
        $this->assertSame(20, $data['y_position']);
        $this->assertSame(1, $data['quest_count']);
        $this->assertSame(1, $data['reward_item_count']);
    }

    public function test_quests_endpoint_returns_only_quests_for_selected_npc(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $npc = $this->createNpc(['game_map_id' => $gameMap->id]);
        $otherNpc = $this->createNpc(['game_map_id' => $gameMap->id]);
        $this->createQuest(['npc_id' => $npc->id, 'name' => 'Npc Quest']);
        $this->createQuest(['npc_id' => $otherNpc->id, 'name' => 'Other Npc Quest']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/npcs/'.$npc->id.'/quests');
        $data = json_decode($response->getContent(), true);
        $names = array_column($data['data'], 'name');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains('Npc Quest', $names);
        $this->assertNotContains('Other Npc Quest', $names);
    }

    public function test_quests_endpoint_exposes_required_secondary_and_reward_item_identities(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $npc = $this->createNpc(['game_map_id' => $gameMap->id]);
        $requiredItem = $this->createItem(['name' => 'Required Item']);
        $secondaryItem = $this->createItem(['name' => 'Secondary Item']);
        $rewardItem = $this->createItem(['name' => 'Reward Item']);
        $this->createQuest([
            'npc_id' => $npc->id,
            'name' => 'Full Quest',
            'item_id' => $requiredItem->id,
            'secondary_required_item' => $secondaryItem->id,
            'reward_item' => $rewardItem->id,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/npcs/'.$npc->id.'/quests');
        $data = json_decode($response->getContent(), true);
        $quest = $data['data'][0];

        $this->assertSame(['id' => $requiredItem->id, 'name' => 'Required Item'], $quest['required_item']);
        $this->assertSame(['id' => $secondaryItem->id, 'name' => 'Secondary Item'], $quest['secondary_required_item']);
        $this->assertSame(['id' => $rewardItem->id, 'name' => 'Reward Item'], $quest['reward_item']);
    }

    public function test_quests_endpoint_leaves_null_item_relationships_null(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $npc = $this->createNpc(['game_map_id' => $gameMap->id]);
        $this->createQuest(['npc_id' => $npc->id, 'name' => 'Bare Quest', 'item_id' => null, 'secondary_required_item' => null, 'reward_item' => null]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/npcs/'.$npc->id.'/quests');
        $data = json_decode($response->getContent(), true);
        $quest = $data['data'][0];

        $this->assertNull($quest['required_item']);
        $this->assertNull($quest['secondary_required_item']);
        $this->assertNull($quest['reward_item']);
    }

    public function test_quests_endpoint_returns_empty_pagination_when_npc_has_no_quests(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $npc = $this->createNpc(['game_map_id' => $gameMap->id]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/npcs/'.$npc->id.'/quests');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], $data['data']);
        $this->assertSame(0, $data['meta']['pagination']['total']);
    }

    public function test_reward_items_endpoint_includes_only_reward_items_from_selected_npcs_quests(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $npc = $this->createNpc(['game_map_id' => $gameMap->id]);
        $otherNpc = $this->createNpc(['game_map_id' => $gameMap->id]);
        $rewardItem = $this->createItem(['name' => 'This Npc Reward']);
        $otherReward = $this->createItem(['name' => 'Other Npc Reward']);
        $this->createQuest(['npc_id' => $npc->id, 'reward_item' => $rewardItem->id]);
        $this->createQuest(['npc_id' => $otherNpc->id, 'reward_item' => $otherReward->id]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/npcs/'.$npc->id.'/reward-items');
        $data = json_decode($response->getContent(), true);
        $names = array_column($data['data'], 'name');

        $this->assertContains('This Npc Reward', $names);
        $this->assertNotContains('Other Npc Reward', $names);
    }

    public function test_reward_items_endpoint_deduplicates_repeated_reward_item(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $npc = $this->createNpc(['game_map_id' => $gameMap->id]);
        $rewardItem = $this->createItem(['name' => 'Shared Reward']);
        $this->createQuest(['npc_id' => $npc->id, 'name' => 'Quest One', 'reward_item' => $rewardItem->id]);
        $this->createQuest(['npc_id' => $npc->id, 'name' => 'Quest Two', 'reward_item' => $rewardItem->id]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/npcs/'.$npc->id.'/reward-items');
        $data = json_decode($response->getContent(), true);
        $names = array_column($data['data'], 'name');

        $this->assertCount(1, $data['data']);
        $this->assertSame(['Shared Reward'], $names);
    }
}
