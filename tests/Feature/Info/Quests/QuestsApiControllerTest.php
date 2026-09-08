<?php

namespace Tests\Feature\Info\Quests;

use App\Flare\GemWorldGeneration\Values\GeneratedGemMapType;
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
        $this->createGameMap(['name' => 'Purgatory', 'default' => false]);
        $this->createGameMap(['name' => 'Shadow Plane', 'default' => false]);
        $this->createGameMap(['name' => 'Dungeons', 'default' => false]);
        $this->createGameMap(['name' => 'Labyrinth', 'default' => false]);
        $surface = $this->createGameMap(['name' => 'Surface', 'default' => true]);

        $response = $this->call('GET', '/api/information/quests/options', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true);

        $response->assertStatus(200);
        $this->assertSame($surface->id, $data['default_game_map_id']);
        $this->assertSame([
            'Surface',
            'Labyrinth',
            'Dungeons',
            'Shadow Plane',
            'Purgatory',
            'Zeta Map',
        ], array_column($data['game_maps'], 'name'));
    }

    public function test_public_quest_browse_options_exclude_generated_game_maps(): void
    {
        $canonical = $this->createGameMap(['name' => 'Public Canonical Selectable Map', 'default' => false]);
        $generated = $this->createGameMap([
            'name' => 'Public Generated Child Map',
            'default' => false,
            'generated_parent_game_map_id' => $canonical->id,
            'generated_map_type' => GeneratedGemMapType::MAP_GEM->value,
        ]);

        $response = $this->call('GET', '/api/information/quests/options', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true);

        $mapIds = array_column($data['game_maps'], 'id');

        $this->assertContains($canonical->id, $mapIds);
        $this->assertNotContains($generated->id, $mapIds);
    }

    public function test_public_quest_browse_options_do_not_use_generated_map_as_default(): void
    {
        $canonical = $this->createGameMap(['name' => 'Public Canonical Fallback Map', 'default' => false]);
        $this->createGameMap([
            'name' => 'Public Generated Default Map',
            'default' => true,
            'generated_parent_game_map_id' => $canonical->id,
            'generated_map_type' => GeneratedGemMapType::LOCATION_GEM->value,
        ]);

        $response = $this->call('GET', '/api/information/quests/options', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true);

        $this->assertNull($data['default_game_map_id']);
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

    public function test_public_quest_detail_returns_required_quest_dependency_structural_facts(): void
    {
        $requiredQuest = $this->createQuest(['name' => 'Public Required Quest']);
        $chainOne = $this->createQuest(['name' => 'Public Chain One']);
        $chainTwo = $this->createQuest(['name' => 'Public Chain Two']);
        $quest = $this->createQuest([
            'name' => 'Public Dependency Quest',
            'required_quest_id' => $requiredQuest->id,
            'required_quest_chain' => [$chainOne->id, $chainTwo->id],
        ]);

        $response = $this->call('GET', "/api/information/quests/{$quest->id}", [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true);

        $this->assertSame($requiredQuest->id, $data['structure']['required_quest']['id']);
        $this->assertArrayHasKey('parent_quest_id', $data['structure']['required_quest']);
        $this->assertArrayHasKey('required_quest_id', $data['structure']['required_quest']);
        $this->assertArrayHasKey('required_quest_chain_ids', $data['structure']['required_quest']);
        $this->assertSame([$chainOne->id, $chainTwo->id], array_column($data['structure']['required_quest_chain'], 'id'));
        $this->assertArrayHasKey('parent_quest_id', $data['structure']['required_quest_chain'][0]);
        $this->assertArrayHasKey('required_quest_id', $data['structure']['required_quest_chain'][0]);
        $this->assertArrayHasKey('required_quest_chain_ids', $data['structure']['required_quest_chain'][0]);
        $this->assertArrayHasKey('parent_quest_id', $data['structure']['required_quest_chain'][1]);
        $this->assertArrayHasKey('required_quest_id', $data['structure']['required_quest_chain'][1]);
        $this->assertArrayHasKey('required_quest_chain_ids', $data['structure']['required_quest_chain'][1]);
    }

    public function test_public_quest_detail_returns_parent_quest_structural_facts(): void
    {
        $parent = $this->createQuest(['name' => 'Public Structural Parent Quest', 'is_parent' => true]);
        $child = $this->createQuest(['name' => 'Public Structural Child Quest', 'parent_quest_id' => $parent->id]);

        $response = $this->call('GET', "/api/information/quests/{$child->id}", [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true);

        $this->assertSame($parent->id, $data['structure']['parent_quest']['id']);
        $this->assertSame('Public Structural Parent Quest', $data['structure']['parent_quest']['name']);
        $this->assertArrayHasKey('parent_quest_id', $data['structure']['parent_quest']);
        $this->assertArrayHasKey('required_quest_id', $data['structure']['parent_quest']);
        $this->assertArrayHasKey('required_quest_chain_ids', $data['structure']['parent_quest']);
    }

    public function test_public_quest_detail_returns_child_quest_structural_facts(): void
    {
        $parent = $this->createQuest(['name' => 'Public Structural Children Parent Quest', 'is_parent' => true]);
        $child = $this->createQuest(['name' => 'Public Structural Children Child Quest', 'parent_quest_id' => $parent->id]);

        $response = $this->call('GET', "/api/information/quests/{$parent->id}", [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true);

        $this->assertSame($child->id, $data['structure']['child_quests'][0]['id']);
        $this->assertSame('Public Structural Children Child Quest', $data['structure']['child_quests'][0]['name']);
        $this->assertArrayHasKey('parent_quest_id', $data['structure']['child_quests'][0]);
        $this->assertArrayHasKey('required_quest_id', $data['structure']['child_quests'][0]);
        $this->assertArrayHasKey('required_quest_chain_ids', $data['structure']['child_quests'][0]);
    }
}
