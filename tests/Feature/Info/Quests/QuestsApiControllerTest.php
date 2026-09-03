<?php

namespace Tests\Feature\Info\Quests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;
use Tests\Traits\CreateRaid;

class QuestsApiControllerTest extends TestCase
{
    use CreateGameMap, CreateLocation, CreateMonster, CreateNpc, CreateQuest, CreateRaid, RefreshDatabase;

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
        $chainRoot = $this->createQuest(['name' => 'B Chain Root', 'is_parent' => true]);
        $this->createQuest(['name' => 'B Chain Child', 'parent_quest_id' => $chainRoot->id]);

        $response = $this->call('GET', '/api/information/quests/tree', ['kind' => 'chain'], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $data = json_decode($response->getContent(), true)['quests'];

        $this->assertCount(1, $data);
        $this->assertSame('B Chain Root', $data[0]['name']);
    }

    public function test_public_quest_tree_map_filter_excludes_chain_when_only_child_matches(): void
    {
        $surface = $this->createGameMap(['name' => 'Surface']);
        $other = $this->createGameMap(['name' => 'Other Map']);
        $rootNpc = $this->createNpc(['game_map_id' => $other->id]);
        $childNpc = $this->createNpc(['game_map_id' => $surface->id]);

        $root = $this->createQuest(['name' => 'Public Root Elsewhere', 'npc_id' => $rootNpc->id, 'is_parent' => true]);
        $this->createQuest(['name' => 'Public Child On Surface', 'npc_id' => $childNpc->id, 'parent_quest_id' => $root->id]);

        $response = $this->call('GET', '/api/information/quests/tree', ['map_id' => $surface->id, 'kind' => 'chain'], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true)['quests'];

        $this->assertCount(0, $data);
    }

    public function test_public_quest_tree_map_filter_returns_full_chain_when_root_matches(): void
    {
        $surface = $this->createGameMap(['name' => 'Surface']);
        $other = $this->createGameMap(['name' => 'Other Map']);
        $rootNpc = $this->createNpc(['game_map_id' => $surface->id]);
        $childNpc = $this->createNpc(['game_map_id' => $other->id]);

        $root = $this->createQuest(['name' => 'Public Root On Surface', 'npc_id' => $rootNpc->id, 'is_parent' => true]);
        $child = $this->createQuest(['name' => 'Public Child Elsewhere', 'npc_id' => $childNpc->id, 'parent_quest_id' => $root->id]);

        $response = $this->call('GET', '/api/information/quests/tree', ['map_id' => $surface->id, 'kind' => 'chain'], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true)['quests'];

        $this->assertCount(1, $data);
        $this->assertSame($root->id, $data[0]['id']);
        $this->assertSame($child->id, $data[0]['children'][0]['id']);
    }

    public function test_public_quest_tree_chain_returns_single_canonical_root_for_selected_map(): void
    {
        $surface = $this->createGameMap(['name' => 'Surface']);
        $firstNpc = $this->createNpc(['game_map_id' => $surface->id]);
        $secondNpc = $this->createNpc(['game_map_id' => $surface->id]);

        $firstRoot = $this->createQuest(['name' => 'Public Alpha Root', 'npc_id' => $firstNpc->id, 'is_parent' => true]);
        $child = $this->createQuest(['name' => 'Public Alpha Child', 'npc_id' => $firstNpc->id, 'parent_quest_id' => $firstRoot->id]);
        $this->createQuest(['name' => 'Public Beta Root', 'npc_id' => $secondNpc->id, 'is_parent' => true]);

        $response = $this->call('GET', '/api/information/quests/tree', ['map_id' => $surface->id, 'kind' => 'chain'], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true)['quests'];

        $this->assertCount(1, $data);
        $this->assertSame('Public Alpha Root', $data[0]['name']);
        $this->assertSame($child->id, $data[0]['children'][0]['id']);
    }

    public function test_public_chain_tree_without_map_filter_returns_multiple_roots(): void
    {
        $alphaRoot = $this->createQuest(['name' => 'Public Alpha Chain Root', 'is_parent' => true]);
        $this->createQuest(['name' => 'Public Alpha Chain Child', 'parent_quest_id' => $alphaRoot->id]);
        $betaRoot = $this->createQuest(['name' => 'Public Beta Chain Root', 'is_parent' => true]);
        $this->createQuest(['name' => 'Public Beta Chain Child', 'parent_quest_id' => $betaRoot->id]);

        $response = $this->call('GET', '/api/information/quests/tree', ['kind' => 'chain'], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true)['quests'];

        $this->assertCount(2, $data);
        $this->assertSame('Public Alpha Chain Root', $data[0]['name']);
        $this->assertSame('Public Beta Chain Root', $data[1]['name']);
    }

    public function test_public_top_level_parent_flagged_quest_without_children_is_one_off(): void
    {
        $surface = $this->createGameMap(['name' => 'Surface']);
        $npc = $this->createNpc(['game_map_id' => $surface->id, 'real_name' => 'Public Surface Giver']);
        $quest = $this->createQuest(['name' => 'Public Surface Parent Flagged One Off', 'npc_id' => $npc->id, 'is_parent' => true]);

        $response = $this->call('GET', '/api/information/quests/tree', ['map_id' => $surface->id, 'kind' => 'one_off'], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true)['quests'];

        $this->assertCount(1, $data);
        $this->assertSame($quest->id, $data[0]['id']);
    }

    public function test_public_top_level_parent_flagged_quest_without_children_is_not_chain(): void
    {
        $surface = $this->createGameMap(['name' => 'Surface']);
        $npc = $this->createNpc(['game_map_id' => $surface->id, 'real_name' => 'Public Surface Giver']);
        $this->createQuest(['name' => 'Public Surface Parent Flagged One Off', 'npc_id' => $npc->id, 'is_parent' => true]);

        $response = $this->call('GET', '/api/information/quests/tree', ['map_id' => $surface->id, 'kind' => 'chain'], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true)['quests'];

        $this->assertCount(0, $data);
    }

    public function test_public_quest_browse_options_are_available_without_auth(): void
    {
        $this->createGameMap(['name' => 'Zeta Map', 'default' => false]);
        $surface = $this->createGameMap(['name' => 'Surface', 'default' => true]);

        $response = $this->call('GET', '/api/information/quests/options', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true);

        $response->assertStatus(200);
        $this->assertSame($surface->id, $data['default_game_map_id']);
        $this->assertSame(['Surface', 'Zeta Map'], array_column($data['game_maps'], 'name'));
    }

    public function test_public_quest_tree_raid_map_filter_uses_raid_location_map_instead_of_quest_giver_map(): void
    {
        $surface = $this->createGameMap(['name' => 'Surface']);
        $hell = $this->createGameMap(['name' => 'Hell']);
        $raidBoss = $this->createMonster(['name' => 'Public Filter Raid Boss']);
        $surfaceRaidLocation = $this->createLocation(['name' => 'Public Surface Raid Location', 'game_map_id' => $surface->id]);
        $hellRaidLocation = $this->createLocation(['name' => 'Public Hell Raid Location', 'game_map_id' => $hell->id]);
        $surfaceRaid = $this->createRaid(['name' => 'Public Surface Raid', 'raid_boss_id' => $raidBoss->id, 'raid_boss_location_id' => $surfaceRaidLocation->id]);
        $hellRaid = $this->createRaid(['name' => 'Public Hell Raid', 'raid_boss_id' => $raidBoss->id, 'raid_boss_location_id' => $hellRaidLocation->id]);

        $surfaceRaidNpc = $this->createNpc(['game_map_id' => $hell->id]);
        $hellRaidNpc = $this->createNpc(['game_map_id' => $surface->id]);
        $surfaceRaidQuest = $this->createQuest(['name' => 'Public Surface Raid Quest', 'npc_id' => $surfaceRaidNpc->id, 'raid_id' => $surfaceRaid->id]);
        $this->createQuest(['name' => 'Public Hell Raid Quest', 'npc_id' => $hellRaidNpc->id, 'raid_id' => $hellRaid->id]);

        $response = $this->call('GET', '/api/information/quests/tree', ['map_id' => $surface->id, 'kind' => 'raid'], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true)['quests'];

        $this->assertCount(1, $data);
        $this->assertSame($surfaceRaidQuest->id, $data[0]['id']);
    }

    public function test_guest_can_access_public_quest_detail(): void
    {
        $quest = $this->createQuest(['name' => 'Detail Quest']);

        $response = $this->call('GET', "/api/information/quests/{$quest->id}", [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(200);
        $this->assertSame('Detail Quest', json_decode($response->getContent(), true)['name']);
    }
}
