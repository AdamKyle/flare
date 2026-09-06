<?php

namespace Tests\Feature\Admin\Quests;

use App\Flare\GemWorldGeneration\Values\GeneratedGemMapType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreatePassiveSkill;
use Tests\Traits\CreateQuest;
use Tests\Traits\CreateRaid;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class QuestsApiControllerTest extends TestCase
{
    use CreateGameMap, CreateItem, CreateLocation, CreateMonster, CreateNpc, CreatePassiveSkill, CreateQuest, CreateRaid, CreateRole, CreateUser, RefreshDatabase;

    public function test_non_admin_cannot_access_quest_tree(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/api/admin/quests/tree', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_quest_tree(): void
    {
        $response = $this->call('GET', '/api/admin/quests/tree', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(401);
    }

    public function test_tree_returns_deterministic_root_order_and_nested_children(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $parent = $this->createQuest(['name' => 'Beta Chain Root']);
        $this->createQuest(['name' => 'Alpha One Off']);
        $child = $this->createQuest(['name' => 'Beta Chain Child', 'parent_quest_id' => $parent->id]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/quests/tree', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $data = json_decode($response->getContent(), true)['quests'];

        $this->assertSame('Alpha One Off', $data[0]['name']);
        $this->assertSame('Beta Chain Root', $data[1]['name']);
        $this->assertSame('chain', $data[1]['kind']);
        $this->assertSame('one_off', $data[0]['kind']);
        $this->assertCount(1, $data[1]['children']);
        $this->assertSame($child->id, $data[1]['children'][0]['id']);
    }

    public function test_tree_kind_resolves_raid_before_chain(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $raidBoss = $this->createMonster(['name' => 'Raid Boss Monster']);
        $raidLocation = $this->createLocation(['name' => 'Raid Boss Location']);
        $raid = $this->createRaid(['name' => 'Test Raid', 'raid_boss_id' => $raidBoss->id, 'raid_boss_location_id' => $raidLocation->id]);

        $this->createQuest(['name' => 'Raid Quest', 'raid_id' => $raid->id, 'is_parent' => true]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/quests/tree', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $data = json_decode($response->getContent(), true)['quests'];

        $this->assertSame('raid', $data[0]['kind']);
    }

    public function test_tree_can_be_filtered_by_game_map(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $mapOne = $this->createGameMap(['name' => 'Map One']);
        $mapTwo = $this->createGameMap(['name' => 'Map Two']);
        $npcOne = $this->createNpc(['game_map_id' => $mapOne->id, 'real_name' => 'Giver One']);
        $npcTwo = $this->createNpc(['game_map_id' => $mapTwo->id, 'real_name' => 'Giver Two']);

        $this->createQuest(['name' => 'On Map One', 'npc_id' => $npcOne->id]);
        $this->createQuest(['name' => 'On Map Two', 'npc_id' => $npcTwo->id]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/quests/tree', ['map_id' => $mapOne->id], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $data = json_decode($response->getContent(), true)['quests'];

        $this->assertCount(1, $data);
        $this->assertSame('On Map One', $data[0]['name']);
    }

    public function test_tree_can_be_filtered_by_kind(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $this->createQuest(['name' => 'A One Off']);
        $chainRoot = $this->createQuest(['name' => 'B Chain Root', 'is_parent' => true]);
        $this->createQuest(['name' => 'B Chain Child', 'parent_quest_id' => $chainRoot->id]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/quests/tree', ['kind' => 'chain'], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $data = json_decode($response->getContent(), true)['quests'];

        $this->assertCount(1, $data);
        $this->assertSame('B Chain Root', $data[0]['name']);
    }

    public function test_show_returns_full_factual_detail_shape(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap(['name' => 'Quest Giver Map']);
        $npc = $this->createNpc(['game_map_id' => $gameMap->id, 'real_name' => 'The Giver']);
        $primaryItem = $this->createItem(['name' => 'Primary Quest Item', 'type' => 'quest']);
        $secondaryItem = $this->createItem(['name' => 'Secondary Quest Item', 'type' => 'quest']);
        $rewardItem = $this->createItem(['name' => 'Reward Item']);
        $accessMap = $this->createGameMap(['name' => 'Access Map']);
        $factionMap = $this->createGameMap(['name' => 'Faction Map']);
        $assistingNpc = $this->createNpc(['game_map_id' => $gameMap->id, 'real_name' => 'Assisting Npc']);
        $passive = $this->createPassiveSkill(['name' => 'Unlocked Passive']);
        $requiredQuest = $this->createQuest(['name' => 'Required Quest']);
        $chainOne = $this->createQuest(['name' => 'Chain One']);
        $chainTwo = $this->createQuest(['name' => 'Chain Two']);
        $parent = $this->createQuest(['name' => 'Parent Quest', 'is_parent' => true]);

        $quest = $this->createQuest([
            'name' => 'Full Detail Quest',
            'npc_id' => $npc->id,
            'item_id' => $primaryItem->id,
            'secondary_required_item' => $secondaryItem->id,
            'reward_item' => $rewardItem->id,
            'access_to_map_id' => $accessMap->id,
            'faction_game_map_id' => $factionMap->id,
            'required_faction_level' => 5,
            'assisting_npc_id' => $assistingNpc->id,
            'required_fame_level' => 7,
            'required_quest_id' => $requiredQuest->id,
            'required_quest_chain' => [$chainOne->id, $chainTwo->id],
            'parent_quest_id' => $parent->id,
            'unlocks_passive_id' => $passive->id,
            'before_completion_description' => '**Before** markdown',
            'after_completion_description' => '**After** markdown',
            'gold_cost' => 10,
            'gold_dust_cost' => 20,
            'shard_cost' => 30,
            'copper_coin_cost' => 40,
            'reward_gold' => 50,
            'reward_gold_dust' => 60,
            'reward_shards' => 70,
            'reward_xp' => 80,
        ]);

        $response = $this->actingAs($admin)->call('GET', "/api/admin/quests/{$quest->id}", [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true);

        $this->assertSame('Full Detail Quest', $data['name']);
        $this->assertSame('**Before** markdown', $data['story']['before_completion_markdown']);
        $this->assertSame('**After** markdown', $data['story']['after_completion_markdown']);
        $this->assertSame('The Giver', $data['npc']['name']);
        $this->assertSame($npc->id, $data['npc']['id']);
        $this->assertSame('Quest Giver Map', $data['npc']['game_map']['name']);
        $this->assertSame($gameMap->id, $data['npc']['game_map']['id']);
        $this->assertSame('Parent Quest', $data['structure']['parent_quest']['name']);
        $this->assertSame($parent->id, $data['structure']['parent_quest']['id']);
        $this->assertArrayHasKey('parent_quest_id', $data['structure']['parent_quest']);
        $this->assertArrayHasKey('required_quest_id', $data['structure']['parent_quest']);
        $this->assertArrayHasKey('required_quest_chain_ids', $data['structure']['parent_quest']);
        $this->assertSame('Required Quest', $data['structure']['required_quest']['name']);
        $this->assertSame($requiredQuest->id, $data['structure']['required_quest']['id']);
        $this->assertArrayHasKey('parent_quest_id', $data['structure']['required_quest']);
        $this->assertArrayHasKey('required_quest_id', $data['structure']['required_quest']);
        $this->assertArrayHasKey('required_quest_chain_ids', $data['structure']['required_quest']);
        $this->assertSame(['Chain One', 'Chain Two'], array_column($data['structure']['required_quest_chain'], 'name'));
        $this->assertSame([$chainOne->id, $chainTwo->id], array_column($data['structure']['required_quest_chain'], 'id'));
        $this->assertArrayHasKey('parent_quest_id', $data['structure']['required_quest_chain'][0]);
        $this->assertArrayHasKey('required_quest_id', $data['structure']['required_quest_chain'][0]);
        $this->assertArrayHasKey('required_quest_chain_ids', $data['structure']['required_quest_chain'][0]);
        $this->assertArrayHasKey('parent_quest_id', $data['structure']['required_quest_chain'][1]);
        $this->assertArrayHasKey('required_quest_id', $data['structure']['required_quest_chain'][1]);
        $this->assertArrayHasKey('required_quest_chain_ids', $data['structure']['required_quest_chain'][1]);
        $this->assertSame('Primary Quest Item', $data['requirements']['primary_item']['name']);
        $this->assertSame($primaryItem->id, $data['requirements']['primary_item']['item_id']);
        $this->assertSame('Secondary Quest Item', $data['requirements']['secondary_item']['name']);
        $this->assertSame($secondaryItem->id, $data['requirements']['secondary_item']['item_id']);
        $this->assertSame('Access Map', $data['requirements']['access_to_map']['name']);
        $this->assertSame($accessMap->id, $data['requirements']['access_to_map']['id']);
        $this->assertSame('Faction Map', $data['requirements']['faction']['game_map']['name']);
        $this->assertSame($factionMap->id, $data['requirements']['faction']['game_map']['id']);
        $this->assertSame(5, $data['requirements']['faction']['required_level']);
        $this->assertSame('Assisting Npc', $data['requirements']['faction_loyalty']['npc']['name']);
        $this->assertSame($assistingNpc->id, $data['requirements']['faction_loyalty']['npc']['id']);
        $this->assertSame(7, $data['requirements']['faction_loyalty']['required_fame_level']);
        $this->assertSame(10, $data['requirements']['currencies']['gold']);
        $this->assertSame(20, $data['requirements']['currencies']['gold_dust']);
        $this->assertSame(30, $data['requirements']['currencies']['shards']);
        $this->assertSame(40, $data['requirements']['currencies']['copper_coins']);
        $this->assertSame('Reward Item', $data['rewards']['item']['name']);
        $this->assertSame($rewardItem->id, $data['rewards']['item']['item_id']);
        $this->assertSame(50, $data['rewards']['gold']);
        $this->assertSame(60, $data['rewards']['gold_dust']);
        $this->assertSame(70, $data['rewards']['shards']);
        $this->assertSame(80, $data['rewards']['xp']);
        $this->assertSame('Unlocked Passive', $data['rewards']['passive']['name']);
        $this->assertSame($passive->id, $data['rewards']['passive']['id']);
    }

    public function test_show_returns_child_quest_ids(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $npc = $this->createNpc();
        $parent = $this->createQuest(['name' => 'Parent With Children', 'npc_id' => $npc->id]);
        $child = $this->createQuest(['name' => 'Child Quest', 'npc_id' => $npc->id, 'parent_quest_id' => $parent->id]);

        $response = $this->actingAs($admin)->call('GET', "/api/admin/quests/{$parent->id}", [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true);

        $this->assertSame([$child->id], array_column($data['structure']['child_quests'], 'id'));
        $this->assertSame(['Child Quest'], array_column($data['structure']['child_quests'], 'name'));
        $this->assertArrayHasKey('parent_quest_id', $data['structure']['child_quests'][0]);
        $this->assertArrayHasKey('required_quest_id', $data['structure']['child_quests'][0]);
        $this->assertArrayHasKey('required_quest_chain_ids', $data['structure']['child_quests'][0]);
    }

    public function test_show_reports_raid_kind_and_raid_identity_consistently(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $npc = $this->createNpc();
        $raidBoss = $this->createMonster(['name' => 'Consistency Raid Boss']);
        $raidLocation = $this->createLocation(['name' => 'Consistency Raid Location']);
        $raid = $this->createRaid([
            'name' => 'Consistency Raid',
            'raid_boss_id' => $raidBoss->id,
            'raid_boss_location_id' => $raidLocation->id,
        ]);
        $quest = $this->createQuest(['name' => 'Raid Kind Quest', 'npc_id' => $npc->id, 'raid_id' => $raid->id]);

        $response = $this->actingAs($admin)->call('GET', "/api/admin/quests/{$quest->id}", [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true);

        $this->assertSame('raid', $data['kind']);
        $this->assertNotNull($data['availability']['raid']);
        $this->assertSame($raid->id, $data['availability']['raid']['id']);
        $this->assertSame('Consistency Raid', $data['availability']['raid']['name']);
    }

    public function test_store_creates_a_quest_from_every_field_group_and_sets_parent_is_parent_flag(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $npc = $this->createNpc(['real_name' => 'A Giver']);
        $item = $this->createItem(['name' => 'A Quest Item', 'type' => 'quest']);
        $parent = $this->createQuest(['name' => 'A Parent Quest']);

        $payload = [
            'name' => 'New Quest',
            'npc_id' => $npc->id,
            'raid_id' => null,
            'only_for_event' => null,
            'before_completion_description' => 'Before text',
            'after_completion_description' => 'After text',
            'parent_quest_id' => $parent->id,
            'required_quest_id' => null,
            'required_quest_chain' => [],
            'reincarnated_times' => 0,
            'item_id' => $item->id,
            'secondary_required_item' => null,
            'access_to_map_id' => null,
            'faction_game_map_id' => null,
            'required_faction_level' => null,
            'assisting_npc_id' => null,
            'required_fame_level' => null,
            'gold_cost' => 1,
            'gold_dust_cost' => 2,
            'shard_cost' => 3,
            'copper_coin_cost' => 4,
            'reward_item' => null,
            'reward_gold' => 5,
            'reward_gold_dust' => 6,
            'reward_shards' => 7,
            'reward_xp' => 8,
            'unlocks_skill' => false,
            'unlocks_skill_type' => null,
            'unlocks_feature' => null,
            'unlocks_passive_id' => null,
        ];

        $response = $this->actingAs($admin)->call('POST', '/api/admin/quests', $payload, [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(201);
        $response->assertJsonPath('name', 'New Quest');

        $this->assertDatabaseHas('quests', ['name' => 'New Quest', 'parent_quest_id' => $parent->id]);
        $this->assertTrue($parent->refresh()->is_parent);
    }

    public function test_update_modifies_every_field_group(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $quest = $this->createQuest(['name' => 'Editable Quest']);
        $rewardItem = $this->createItem(['name' => 'New Reward Item']);
        $newNpc = $this->createNpc(['real_name' => 'Renamed Quest Giver']);

        $payload = [
            'name' => 'Renamed Quest',
            'npc_id' => $newNpc->id,
            'raid_id' => null,
            'only_for_event' => null,
            'before_completion_description' => null,
            'after_completion_description' => null,
            'parent_quest_id' => null,
            'required_quest_id' => null,
            'required_quest_chain' => [],
            'reincarnated_times' => 2,
            'item_id' => null,
            'secondary_required_item' => null,
            'access_to_map_id' => null,
            'faction_game_map_id' => null,
            'required_faction_level' => null,
            'assisting_npc_id' => null,
            'required_fame_level' => null,
            'gold_cost' => 100,
            'gold_dust_cost' => 100,
            'shard_cost' => 100,
            'copper_coin_cost' => 100,
            'reward_item' => $rewardItem->id,
            'reward_gold' => 999,
            'reward_gold_dust' => 999,
            'reward_shards' => 999,
            'reward_xp' => 999,
            'unlocks_skill' => false,
            'unlocks_skill_type' => null,
            'unlocks_feature' => null,
            'unlocks_passive_id' => null,
        ];

        $response = $this->actingAs($admin)->call('PUT', "/api/admin/quests/{$quest->id}", $payload, [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('quests', ['id' => $quest->id, 'name' => 'Renamed Quest', 'reward_item' => $rewardItem->id, 'reward_xp' => 999]);
    }

    public function test_self_parent_is_rejected(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $quest = $this->createQuest(['name' => 'Self Parent Quest']);

        $payload = [
            'name' => 'Minimal Quest', 'npc_id' => $quest->npc_id, 'raid_id' => null, 'only_for_event' => null,
            'before_completion_description' => null, 'after_completion_description' => null, 'parent_quest_id' => $quest->id,
            'required_quest_id' => null, 'required_quest_chain' => [], 'reincarnated_times' => null, 'item_id' => null,
            'secondary_required_item' => null, 'access_to_map_id' => null, 'faction_game_map_id' => null,
            'required_faction_level' => null, 'assisting_npc_id' => null, 'required_fame_level' => null,
            'gold_cost' => null, 'gold_dust_cost' => null, 'shard_cost' => null, 'copper_coin_cost' => null,
            'reward_item' => null, 'reward_gold' => null, 'reward_gold_dust' => null, 'reward_shards' => null,
            'reward_xp' => null, 'unlocks_skill' => false, 'unlocks_skill_type' => null, 'unlocks_feature' => null,
            'unlocks_passive_id' => null,
        ];

        $response = $this->actingAs($admin)->call('PUT', "/api/admin/quests/{$quest->id}", $payload, [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('parent_quest_id');
    }

    public function test_descendant_selected_as_parent_is_rejected(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $grandparent = $this->createQuest(['name' => 'Grandparent']);
        $parent = $this->createQuest(['name' => 'Parent', 'parent_quest_id' => $grandparent->id]);
        $child = $this->createQuest(['name' => 'Child', 'parent_quest_id' => $parent->id]);

        $payload = [
            'name' => 'Minimal Quest', 'npc_id' => $grandparent->npc_id, 'raid_id' => null, 'only_for_event' => null,
            'before_completion_description' => null, 'after_completion_description' => null, 'parent_quest_id' => $child->id,
            'required_quest_id' => null, 'required_quest_chain' => [], 'reincarnated_times' => null, 'item_id' => null,
            'secondary_required_item' => null, 'access_to_map_id' => null, 'faction_game_map_id' => null,
            'required_faction_level' => null, 'assisting_npc_id' => null, 'required_fame_level' => null,
            'gold_cost' => null, 'gold_dust_cost' => null, 'shard_cost' => null, 'copper_coin_cost' => null,
            'reward_item' => null, 'reward_gold' => null, 'reward_gold_dust' => null, 'reward_shards' => null,
            'reward_xp' => null, 'unlocks_skill' => false, 'unlocks_skill_type' => null, 'unlocks_feature' => null,
            'unlocks_passive_id' => null,
        ];

        $response = $this->actingAs($admin)->call('PUT', "/api/admin/quests/{$grandparent->id}", $payload, [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('parent_quest_id');
    }

    public function test_self_required_quest_is_rejected(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $quest = $this->createQuest(['name' => 'Self Required Quest']);

        $payload = [
            'name' => 'Minimal Quest', 'npc_id' => $quest->npc_id, 'raid_id' => null, 'only_for_event' => null,
            'before_completion_description' => null, 'after_completion_description' => null, 'parent_quest_id' => null,
            'required_quest_id' => $quest->id, 'required_quest_chain' => [], 'reincarnated_times' => null, 'item_id' => null,
            'secondary_required_item' => null, 'access_to_map_id' => null, 'faction_game_map_id' => null,
            'required_faction_level' => null, 'assisting_npc_id' => null, 'required_fame_level' => null,
            'gold_cost' => null, 'gold_dust_cost' => null, 'shard_cost' => null, 'copper_coin_cost' => null,
            'reward_item' => null, 'reward_gold' => null, 'reward_gold_dust' => null, 'reward_shards' => null,
            'reward_xp' => null, 'unlocks_skill' => false, 'unlocks_skill_type' => null, 'unlocks_feature' => null,
            'unlocks_passive_id' => null,
        ];

        $response = $this->actingAs($admin)->call('PUT', "/api/admin/quests/{$quest->id}", $payload, [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('required_quest_id');
    }

    public function test_required_quest_cycle_is_rejected(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $questA = $this->createQuest(['name' => 'Cycle A']);
        $questB = $this->createQuest(['name' => 'Cycle B', 'required_quest_id' => $questA->id]);

        $payload = [
            'name' => 'Minimal Quest', 'npc_id' => $questA->npc_id, 'raid_id' => null, 'only_for_event' => null,
            'before_completion_description' => null, 'after_completion_description' => null, 'parent_quest_id' => null,
            'required_quest_id' => $questB->id, 'required_quest_chain' => [], 'reincarnated_times' => null, 'item_id' => null,
            'secondary_required_item' => null, 'access_to_map_id' => null, 'faction_game_map_id' => null,
            'required_faction_level' => null, 'assisting_npc_id' => null, 'required_fame_level' => null,
            'gold_cost' => null, 'gold_dust_cost' => null, 'shard_cost' => null, 'copper_coin_cost' => null,
            'reward_item' => null, 'reward_gold' => null, 'reward_gold_dust' => null, 'reward_shards' => null,
            'reward_xp' => null, 'unlocks_skill' => false, 'unlocks_skill_type' => null, 'unlocks_feature' => null,
            'unlocks_passive_id' => null,
        ];

        $response = $this->actingAs($admin)->call('PUT', "/api/admin/quests/{$questA->id}", $payload, [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('required_quest_id');
    }

    public function test_self_in_required_chain_is_rejected(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $quest = $this->createQuest(['name' => 'Chain Self Quest']);

        $payload = [
            'name' => 'Minimal Quest', 'npc_id' => $quest->npc_id, 'raid_id' => null, 'only_for_event' => null,
            'before_completion_description' => null, 'after_completion_description' => null, 'parent_quest_id' => null,
            'required_quest_id' => null, 'required_quest_chain' => [$quest->id], 'reincarnated_times' => null, 'item_id' => null,
            'secondary_required_item' => null, 'access_to_map_id' => null, 'faction_game_map_id' => null,
            'required_faction_level' => null, 'assisting_npc_id' => null, 'required_fame_level' => null,
            'gold_cost' => null, 'gold_dust_cost' => null, 'shard_cost' => null, 'copper_coin_cost' => null,
            'reward_item' => null, 'reward_gold' => null, 'reward_gold_dust' => null, 'reward_shards' => null,
            'reward_xp' => null, 'unlocks_skill' => false, 'unlocks_skill_type' => null, 'unlocks_feature' => null,
            'unlocks_passive_id' => null,
        ];

        $response = $this->actingAs($admin)->call('PUT', "/api/admin/quests/{$quest->id}", $payload, [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('required_quest_chain');
    }

    public function test_duplicate_required_chain_ids_are_rejected(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $quest = $this->createQuest(['name' => 'Quest With Chain']);
        $other = $this->createQuest(['name' => 'Other Quest']);

        $payload = [
            'name' => 'Minimal Quest', 'npc_id' => $quest->npc_id, 'raid_id' => null, 'only_for_event' => null,
            'before_completion_description' => null, 'after_completion_description' => null, 'parent_quest_id' => null,
            'required_quest_id' => null, 'required_quest_chain' => [$other->id, $other->id], 'reincarnated_times' => null, 'item_id' => null,
            'secondary_required_item' => null, 'access_to_map_id' => null, 'faction_game_map_id' => null,
            'required_faction_level' => null, 'assisting_npc_id' => null, 'required_fame_level' => null,
            'gold_cost' => null, 'gold_dust_cost' => null, 'shard_cost' => null, 'copper_coin_cost' => null,
            'reward_item' => null, 'reward_gold' => null, 'reward_gold_dust' => null, 'reward_shards' => null,
            'reward_xp' => null, 'unlocks_skill' => false, 'unlocks_skill_type' => null, 'unlocks_feature' => null,
            'unlocks_passive_id' => null,
        ];

        $response = $this->actingAs($admin)->call('PUT', "/api/admin/quests/{$quest->id}", $payload, [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('required_quest_chain');
    }

    public function test_missing_required_chain_id_is_rejected(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $quest = $this->createQuest(['name' => 'Quest With Missing Chain']);

        $payload = [
            'name' => 'Minimal Quest', 'npc_id' => $quest->npc_id, 'raid_id' => null, 'only_for_event' => null,
            'before_completion_description' => null, 'after_completion_description' => null, 'parent_quest_id' => null,
            'required_quest_id' => null, 'required_quest_chain' => [999999], 'reincarnated_times' => null, 'item_id' => null,
            'secondary_required_item' => null, 'access_to_map_id' => null, 'faction_game_map_id' => null,
            'required_faction_level' => null, 'assisting_npc_id' => null, 'required_fame_level' => null,
            'gold_cost' => null, 'gold_dust_cost' => null, 'shard_cost' => null, 'copper_coin_cost' => null,
            'reward_item' => null, 'reward_gold' => null, 'reward_gold_dust' => null, 'reward_shards' => null,
            'reward_xp' => null, 'unlocks_skill' => false, 'unlocks_skill_type' => null, 'unlocks_feature' => null,
            'unlocks_passive_id' => null,
        ];

        $response = $this->actingAs($admin)->call('PUT', "/api/admin/quests/{$quest->id}", $payload, [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('required_quest_chain');
    }

    public function test_invalid_relation_ids_are_rejected(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $quest = $this->createQuest(['name' => 'Invalid Relations Quest']);

        $payload = [
            'name' => 'Minimal Quest', 'npc_id' => 999999, 'raid_id' => null, 'only_for_event' => null,
            'before_completion_description' => null, 'after_completion_description' => null, 'parent_quest_id' => null,
            'required_quest_id' => null, 'required_quest_chain' => [], 'reincarnated_times' => null, 'item_id' => null,
            'secondary_required_item' => null, 'access_to_map_id' => null, 'faction_game_map_id' => null,
            'required_faction_level' => null, 'assisting_npc_id' => null, 'required_fame_level' => null,
            'gold_cost' => null, 'gold_dust_cost' => null, 'shard_cost' => null, 'copper_coin_cost' => null,
            'reward_item' => null, 'reward_gold' => null, 'reward_gold_dust' => null, 'reward_shards' => null,
            'reward_xp' => null, 'unlocks_skill' => false, 'unlocks_skill_type' => null, 'unlocks_feature' => null,
            'unlocks_passive_id' => null,
        ];

        $response = $this->actingAs($admin)->call('PUT', "/api/admin/quests/{$quest->id}", $payload, [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('npc_id');
    }

    public function test_new_parent_becomes_parent(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $parent = $this->createQuest(['name' => 'New Parent Quest']);
        $child = $this->createQuest(['name' => 'New Child Quest']);

        $payload = [
            'name' => 'New Child Quest', 'npc_id' => $child->npc_id, 'raid_id' => null, 'only_for_event' => null,
            'before_completion_description' => null, 'after_completion_description' => null, 'parent_quest_id' => $parent->id,
            'required_quest_id' => null, 'required_quest_chain' => [], 'reincarnated_times' => null, 'item_id' => null,
            'secondary_required_item' => null, 'access_to_map_id' => null, 'faction_game_map_id' => null,
            'required_faction_level' => null, 'assisting_npc_id' => null, 'required_fame_level' => null,
            'gold_cost' => null, 'gold_dust_cost' => null, 'shard_cost' => null, 'copper_coin_cost' => null,
            'reward_item' => null, 'reward_gold' => null, 'reward_gold_dust' => null, 'reward_shards' => null,
            'reward_xp' => null, 'unlocks_skill' => false, 'unlocks_skill_type' => null, 'unlocks_feature' => null,
            'unlocks_passive_id' => null,
        ];

        $response = $this->actingAs($admin)->call('PUT', "/api/admin/quests/{$child->id}", $payload, [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('quests', ['id' => $parent->id, 'is_parent' => true]);
    }

    public function test_previous_parent_remains_true_when_it_still_has_another_child(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $parent = $this->createQuest(['name' => 'Shared Parent Quest', 'is_parent' => true]);
        $movingChild = $this->createQuest(['name' => 'Moving Child Quest', 'parent_quest_id' => $parent->id]);
        $this->createQuest(['name' => 'Remaining Child Quest', 'parent_quest_id' => $parent->id]);
        $otherParent = $this->createQuest(['name' => 'Other Parent Quest']);

        $payload = [
            'name' => 'Moving Child Quest', 'npc_id' => $movingChild->npc_id, 'raid_id' => null, 'only_for_event' => null,
            'before_completion_description' => null, 'after_completion_description' => null, 'parent_quest_id' => $otherParent->id,
            'required_quest_id' => null, 'required_quest_chain' => [], 'reincarnated_times' => null, 'item_id' => null,
            'secondary_required_item' => null, 'access_to_map_id' => null, 'faction_game_map_id' => null,
            'required_faction_level' => null, 'assisting_npc_id' => null, 'required_fame_level' => null,
            'gold_cost' => null, 'gold_dust_cost' => null, 'shard_cost' => null, 'copper_coin_cost' => null,
            'reward_item' => null, 'reward_gold' => null, 'reward_gold_dust' => null, 'reward_shards' => null,
            'reward_xp' => null, 'unlocks_skill' => false, 'unlocks_skill_type' => null, 'unlocks_feature' => null,
            'unlocks_passive_id' => null,
        ];

        $response = $this->actingAs($admin)->call('PUT', "/api/admin/quests/{$movingChild->id}", $payload, [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('quests', ['id' => $parent->id, 'is_parent' => true]);
    }

    public function test_previous_parent_becomes_false_when_its_final_child_moves(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $parent = $this->createQuest(['name' => 'Only Parent Quest', 'is_parent' => true]);
        $child = $this->createQuest(['name' => 'Only Child Quest', 'parent_quest_id' => $parent->id]);
        $newParent = $this->createQuest(['name' => 'New Destination Parent Quest']);

        $payload = [
            'name' => 'Only Child Quest', 'npc_id' => $child->npc_id, 'raid_id' => null, 'only_for_event' => null,
            'before_completion_description' => null, 'after_completion_description' => null, 'parent_quest_id' => $newParent->id,
            'required_quest_id' => null, 'required_quest_chain' => [], 'reincarnated_times' => null, 'item_id' => null,
            'secondary_required_item' => null, 'access_to_map_id' => null, 'faction_game_map_id' => null,
            'required_faction_level' => null, 'assisting_npc_id' => null, 'required_fame_level' => null,
            'gold_cost' => null, 'gold_dust_cost' => null, 'shard_cost' => null, 'copper_coin_cost' => null,
            'reward_item' => null, 'reward_gold' => null, 'reward_gold_dust' => null, 'reward_shards' => null,
            'reward_xp' => null, 'unlocks_skill' => false, 'unlocks_skill_type' => null, 'unlocks_feature' => null,
            'unlocks_passive_id' => null,
        ];

        $response = $this->actingAs($admin)->call('PUT', "/api/admin/quests/{$child->id}", $payload, [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('quests', ['id' => $parent->id, 'is_parent' => false]);
    }

    public function test_tree_map_filter_returns_full_chain_when_root_belongs_to_selected_map(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $surface = $this->createGameMap(['name' => 'Surface']);
        $other = $this->createGameMap(['name' => 'Other Map']);
        $rootNpc = $this->createNpc(['game_map_id' => $surface->id, 'real_name' => 'Root Giver']);
        $childNpc = $this->createNpc(['game_map_id' => $other->id, 'real_name' => 'Child Giver']);

        $root = $this->createQuest(['name' => 'Root On Surface', 'npc_id' => $rootNpc->id, 'is_parent' => true]);
        $this->createQuest(['name' => 'Child Elsewhere', 'npc_id' => $childNpc->id, 'parent_quest_id' => $root->id]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/quests/tree', ['map_id' => $surface->id, 'kind' => 'chain'], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true)['quests'];

        $this->assertCount(1, $data);
        $this->assertSame('Root On Surface', $data[0]['name']);
        $this->assertCount(1, $data[0]['children']);
        $this->assertSame('Child Elsewhere', $data[0]['children'][0]['name']);
    }

    public function test_tree_chain_returns_single_canonical_root_for_selected_map(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $surface = $this->createGameMap(['name' => 'Surface']);
        $firstNpc = $this->createNpc(['game_map_id' => $surface->id, 'real_name' => 'Alpha Giver']);
        $secondNpc = $this->createNpc(['game_map_id' => $surface->id, 'real_name' => 'Beta Giver']);

        $firstRoot = $this->createQuest(['name' => 'Alpha Root', 'npc_id' => $firstNpc->id, 'is_parent' => true]);
        $child = $this->createQuest(['name' => 'Alpha Child', 'npc_id' => $firstNpc->id, 'parent_quest_id' => $firstRoot->id]);
        $this->createQuest(['name' => 'Beta Root', 'npc_id' => $secondNpc->id, 'is_parent' => true]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/quests/tree', ['map_id' => $surface->id, 'kind' => 'chain'], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true)['quests'];

        $this->assertCount(1, $data);
        $this->assertSame('Alpha Root', $data[0]['name']);
        $this->assertSame($child->id, $data[0]['children'][0]['id']);
    }

    public function test_chain_tree_without_map_filter_returns_multiple_roots(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $alphaRoot = $this->createQuest(['name' => 'Alpha Chain Root', 'is_parent' => true]);
        $this->createQuest(['name' => 'Alpha Chain Child', 'parent_quest_id' => $alphaRoot->id]);
        $betaRoot = $this->createQuest(['name' => 'Beta Chain Root', 'is_parent' => true]);
        $this->createQuest(['name' => 'Beta Chain Child', 'parent_quest_id' => $betaRoot->id]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/quests/tree', ['kind' => 'chain'], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true)['quests'];

        $this->assertCount(2, $data);
        $this->assertSame('Alpha Chain Root', $data[0]['name']);
        $this->assertSame('Beta Chain Root', $data[1]['name']);
    }

    public function test_top_level_parent_flagged_quest_without_children_is_one_off(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $surface = $this->createGameMap(['name' => 'Surface']);
        $npc = $this->createNpc(['game_map_id' => $surface->id, 'real_name' => 'Surface Giver']);
        $quest = $this->createQuest(['name' => 'Surface Parent Flagged One Off', 'npc_id' => $npc->id, 'is_parent' => true]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/quests/tree', ['map_id' => $surface->id, 'kind' => 'one_off'], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true)['quests'];

        $this->assertCount(1, $data);
        $this->assertSame($quest->id, $data[0]['id']);
    }

    public function test_top_level_parent_flagged_quest_without_children_is_not_chain(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $surface = $this->createGameMap(['name' => 'Surface']);
        $npc = $this->createNpc(['game_map_id' => $surface->id, 'real_name' => 'Surface Giver']);
        $this->createQuest(['name' => 'Surface Parent Flagged One Off', 'npc_id' => $npc->id, 'is_parent' => true]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/quests/tree', ['map_id' => $surface->id, 'kind' => 'chain'], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true)['quests'];

        $this->assertCount(0, $data);
    }

    public function test_tree_map_filter_excludes_chain_when_only_child_belongs_to_selected_map(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $surface = $this->createGameMap(['name' => 'Surface']);
        $other = $this->createGameMap(['name' => 'Other Map']);
        $rootNpc = $this->createNpc(['game_map_id' => $other->id, 'real_name' => 'Root Giver']);
        $childNpc = $this->createNpc(['game_map_id' => $surface->id, 'real_name' => 'Child Giver']);

        $root = $this->createQuest(['name' => 'Root Elsewhere', 'npc_id' => $rootNpc->id, 'is_parent' => true]);
        $this->createQuest(['name' => 'Child On Surface', 'npc_id' => $childNpc->id, 'parent_quest_id' => $root->id]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/quests/tree', ['map_id' => $surface->id, 'kind' => 'chain'], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true)['quests'];

        $this->assertCount(0, $data);
    }

    public function test_tree_map_filter_excludes_chain_when_only_grandchild_belongs_to_selected_map(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $surface = $this->createGameMap(['name' => 'Surface']);
        $other = $this->createGameMap(['name' => 'Other Map']);
        $rootNpc = $this->createNpc(['game_map_id' => $other->id]);
        $childNpc = $this->createNpc(['game_map_id' => $other->id]);
        $grandchildNpc = $this->createNpc(['game_map_id' => $surface->id]);

        $root = $this->createQuest(['name' => 'Root Elsewhere', 'npc_id' => $rootNpc->id, 'is_parent' => true]);
        $child = $this->createQuest(['name' => 'Child Elsewhere', 'npc_id' => $childNpc->id, 'parent_quest_id' => $root->id, 'is_parent' => true]);
        $this->createQuest(['name' => 'Grandchild On Surface', 'npc_id' => $grandchildNpc->id, 'parent_quest_id' => $child->id]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/quests/tree', ['map_id' => $surface->id, 'kind' => 'chain'], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true)['quests'];

        $this->assertCount(0, $data);
    }

    public function test_tree_map_filter_excludes_chain_when_no_member_belongs_to_selected_map(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $surface = $this->createGameMap(['name' => 'Surface']);
        $other = $this->createGameMap(['name' => 'Other Map']);
        $rootNpc = $this->createNpc(['game_map_id' => $other->id]);
        $childNpc = $this->createNpc(['game_map_id' => $other->id]);

        $root = $this->createQuest(['name' => 'Root Never Surface', 'npc_id' => $rootNpc->id, 'is_parent' => true]);
        $this->createQuest(['name' => 'Child Never Surface', 'npc_id' => $childNpc->id, 'parent_quest_id' => $root->id]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/quests/tree', ['map_id' => $surface->id, 'kind' => 'chain'], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true)['quests'];

        $this->assertCount(0, $data);
    }

    public function test_tree_map_filter_one_off_matches_only_its_own_map(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $surface = $this->createGameMap(['name' => 'Surface']);
        $other = $this->createGameMap(['name' => 'Other Map']);
        $surfaceNpc = $this->createNpc(['game_map_id' => $surface->id]);
        $otherNpc = $this->createNpc(['game_map_id' => $other->id]);

        $this->createQuest(['name' => 'Surface One Off', 'npc_id' => $surfaceNpc->id]);
        $this->createQuest(['name' => 'Other One Off', 'npc_id' => $otherNpc->id]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/quests/tree', ['map_id' => $surface->id, 'kind' => 'one_off'], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true)['quests'];

        $this->assertCount(1, $data);
        $this->assertSame('Surface One Off', $data[0]['name']);
    }

    public function test_tree_map_filter_raid_uses_raid_location_map_instead_of_quest_giver_map(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $surface = $this->createGameMap(['name' => 'Surface']);
        $hell = $this->createGameMap(['name' => 'Hell']);
        $raidBoss = $this->createMonster(['name' => 'Filter Raid Boss']);
        $surfaceRaidLocation = $this->createLocation(['name' => 'Surface Raid Location', 'game_map_id' => $surface->id]);
        $hellRaidLocation = $this->createLocation(['name' => 'Hell Raid Location', 'game_map_id' => $hell->id]);
        $surfaceRaid = $this->createRaid(['name' => 'Surface Raid', 'raid_boss_id' => $raidBoss->id, 'raid_boss_location_id' => $surfaceRaidLocation->id]);
        $hellRaid = $this->createRaid(['name' => 'Hell Raid', 'raid_boss_id' => $raidBoss->id, 'raid_boss_location_id' => $hellRaidLocation->id]);

        $surfaceRaidNpc = $this->createNpc(['game_map_id' => $hell->id]);
        $hellRaidNpc = $this->createNpc(['game_map_id' => $surface->id]);
        $surfaceRaidQuest = $this->createQuest(['name' => 'Surface Raid Quest', 'npc_id' => $surfaceRaidNpc->id, 'raid_id' => $surfaceRaid->id]);
        $this->createQuest(['name' => 'Hell Raid Quest', 'npc_id' => $hellRaidNpc->id, 'raid_id' => $hellRaid->id]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/quests/tree', ['map_id' => $surface->id, 'kind' => 'raid'], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true)['quests'];

        $this->assertCount(1, $data);
        $this->assertSame($surfaceRaidQuest->id, $data[0]['id']);
    }

    public function test_tree_map_filter_raid_matches_corrupted_location_map(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $selectedMap = $this->createGameMap(['name' => 'Selected Map']);
        $otherMap = $this->createGameMap(['name' => 'Other Map']);
        $raidBoss = $this->createMonster(['name' => 'Corrupted Raid Boss']);
        $bossLocation = $this->createLocation(['name' => 'Boss Location', 'game_map_id' => $otherMap->id]);
        $corruptedLocation = $this->createLocation(['name' => 'Corrupted Location', 'game_map_id' => $selectedMap->id]);
        $raid = $this->createRaid([
            'name' => 'Corrupted Raid',
            'raid_boss_id' => $raidBoss->id,
            'raid_boss_location_id' => $bossLocation->id,
            'corrupted_location_ids' => [$corruptedLocation->id],
        ]);

        $raidNpc = $this->createNpc(['game_map_id' => $otherMap->id]);
        $raidQuest = $this->createQuest(['name' => 'Corrupted Raid Quest', 'npc_id' => $raidNpc->id, 'raid_id' => $raid->id]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/quests/tree', ['map_id' => $selectedMap->id, 'kind' => 'raid'], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true)['quests'];

        $this->assertCount(1, $data);
        $this->assertSame($raidQuest->id, $data[0]['id']);
    }

    public function test_tree_map_filter_raid_excludes_raid_whose_quest_npc_matches_but_locations_do_not(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $selectedMap = $this->createGameMap(['name' => 'Selected Map']);
        $otherMap = $this->createGameMap(['name' => 'Other Map']);
        $raidBoss = $this->createMonster(['name' => 'Elsewhere Raid Boss']);
        $bossLocation = $this->createLocation(['name' => 'Elsewhere Boss Location', 'game_map_id' => $otherMap->id]);
        $corruptedLocation = $this->createLocation(['name' => 'Elsewhere Corrupted Location', 'game_map_id' => $otherMap->id]);
        $raid = $this->createRaid([
            'name' => 'Elsewhere Raid',
            'raid_boss_id' => $raidBoss->id,
            'raid_boss_location_id' => $bossLocation->id,
            'corrupted_location_ids' => [$corruptedLocation->id],
        ]);

        $misleadingNpc = $this->createNpc(['game_map_id' => $selectedMap->id]);
        $this->createQuest(['name' => 'Misleading Raid Quest', 'npc_id' => $misleadingNpc->id, 'raid_id' => $raid->id]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/quests/tree', ['map_id' => $selectedMap->id, 'kind' => 'raid'], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true)['quests'];

        $this->assertCount(0, $data);
    }

    public function test_browse_options_returns_default_game_map_and_ordered_maps(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createGameMap(['name' => 'Zeta Map', 'default' => false]);
        $surface = $this->createGameMap(['name' => 'Surface', 'default' => true]);
        $this->createGameMap(['name' => 'Alpha Map', 'default' => false]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/quests/browse-options', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true);

        $response->assertStatus(200);
        $this->assertSame($surface->id, $data['default_game_map_id']);
        $this->assertSame(['Alpha Map', 'Surface', 'Zeta Map'], array_column($data['game_maps'], 'name'));
    }

    public function test_browse_options_returns_null_default_when_no_default_map_exists(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createGameMap(['name' => 'Non Default Map', 'default' => false]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/quests/browse-options', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true);

        $response->assertStatus(200);
        $this->assertNull($data['default_game_map_id']);
    }

    public function test_non_admin_cannot_access_quest_browse_options(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/api/admin/quests/browse-options', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(403);
    }

    public function test_browse_options_exclude_generated_game_maps(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $canonical = $this->createGameMap(['name' => 'Canonical Selectable Map', 'default' => false]);
        $generated = $this->createGameMap([
            'name' => 'Generated Child Map',
            'default' => false,
            'generated_parent_game_map_id' => $canonical->id,
            'generated_map_type' => GeneratedGemMapType::MAP_GEM->value,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/quests/browse-options', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true);

        $mapIds = array_column($data['game_maps'], 'id');
        $mapNames = array_column($data['game_maps'], 'name');

        $this->assertContains($canonical->id, $mapIds);
        $this->assertContains('Canonical Selectable Map', $mapNames);
        $this->assertNotContains($generated->id, $mapIds);
        $this->assertNotContains('Generated Child Map', $mapNames);
    }

    public function test_browse_options_do_not_use_generated_map_as_default(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $canonical = $this->createGameMap(['name' => 'Canonical Fallback Map', 'default' => false]);
        $generated = $this->createGameMap([
            'name' => 'Generated Default Map',
            'default' => true,
            'generated_parent_game_map_id' => $canonical->id,
            'generated_map_type' => GeneratedGemMapType::LOCATION_GEM->value,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/quests/browse-options', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true);

        $mapIds = array_column($data['game_maps'], 'id');

        $this->assertNotContains($generated->id, $mapIds);
        $this->assertNull($data['default_game_map_id']);
    }

    public function test_browse_options_return_only_canonical_map_when_generated_map_has_same_name(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $canonical = $this->createGameMap(['name' => 'Surface Test', 'default' => false]);
        $this->createGameMap([
            'name' => 'Surface Test',
            'default' => false,
            'generated_parent_game_map_id' => $canonical->id,
            'generated_map_type' => GeneratedGemMapType::MAP_GEM->value,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/quests/browse-options', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $data = json_decode($response->getContent(), true);

        $matchingIds = array_values(array_column(
            array_filter($data['game_maps'], fn (array $gameMap) => $gameMap['name'] === 'Surface Test'),
            'id'
        ));

        $this->assertSame([$canonical->id], $matchingIds);
    }

    public function test_previous_parent_becomes_false_when_final_childs_parent_is_cleared(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $parent = $this->createQuest(['name' => 'Cleared Parent Quest', 'is_parent' => true]);
        $child = $this->createQuest(['name' => 'Cleared Child Quest', 'parent_quest_id' => $parent->id]);

        $payload = [
            'name' => 'Cleared Child Quest', 'npc_id' => $child->npc_id, 'raid_id' => null, 'only_for_event' => null,
            'before_completion_description' => null, 'after_completion_description' => null, 'parent_quest_id' => null,
            'required_quest_id' => null, 'required_quest_chain' => [], 'reincarnated_times' => null, 'item_id' => null,
            'secondary_required_item' => null, 'access_to_map_id' => null, 'faction_game_map_id' => null,
            'required_faction_level' => null, 'assisting_npc_id' => null, 'required_fame_level' => null,
            'gold_cost' => null, 'gold_dust_cost' => null, 'shard_cost' => null, 'copper_coin_cost' => null,
            'reward_item' => null, 'reward_gold' => null, 'reward_gold_dust' => null, 'reward_shards' => null,
            'reward_xp' => null, 'unlocks_skill' => false, 'unlocks_skill_type' => null, 'unlocks_feature' => null,
            'unlocks_passive_id' => null,
        ];

        $response = $this->actingAs($admin)->call('PUT', "/api/admin/quests/{$child->id}", $payload, [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('quests', ['id' => $parent->id, 'is_parent' => false]);
    }
}
