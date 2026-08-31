<?php

namespace Tests\Feature\Admin\Quests;

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
        $this->createQuest(['name' => 'B Chain Root', 'is_parent' => true]);

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
        $this->assertSame('Required Quest', $data['structure']['required_quest']['name']);
        $this->assertSame($requiredQuest->id, $data['structure']['required_quest']['id']);
        $this->assertSame(['Chain One', 'Chain Two'], array_column($data['structure']['required_quest_chain'], 'name'));
        $this->assertSame([$chainOne->id, $chainTwo->id], array_column($data['structure']['required_quest_chain'], 'id'));
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
