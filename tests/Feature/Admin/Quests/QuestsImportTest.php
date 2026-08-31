<?php

namespace Tests\Feature\Admin\Quests;

use App\Admin\Quests\Imports\Sheets\QuestsSheet;
use App\Flare\Models\Quest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreatePassiveSkill;
use Tests\Traits\CreateQuest;
use Tests\Traits\CreateRaid;

class QuestsImportTest extends TestCase
{
    use CreateGameMap, CreateItem, CreateNpc, CreatePassiveSkill, CreateQuest, CreateRaid, RefreshDatabase;

    private const HEADERS = [
        'id', 'name', 'npc_id', 'item_id', 'raid_id', 'required_quest_id', 'parent_chain_quest_id',
        'required_quest_chain', 'reincarnated_times', 'access_to_map_id', 'gold_dust_cost', 'shard_cost',
        'gold_cost', 'copper_coin_cost', 'reward_item', 'reward_gold_dust', 'reward_shards', 'reward_gold',
        'reward_xp', 'unlocks_skill', 'unlocks_skill_type', 'is_parent', 'parent_quest_id',
        'secondary_required_item', 'faction_game_map_id', 'required_faction_level', 'before_completion_description',
        'after_completion_description', 'unlocks_feature', 'unlocks_passive_id', 'only_for_event',
        'assisting_npc_id', 'required_fame_level',
    ];

    public function test_two_new_quests_in_same_workbook_where_child_references_parent(): void
    {
        $npc = $this->createNpc();

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => (['name' => 'Workbook Parent', 'npc_id' => $npc->real_name][$header] ?? null), self::HEADERS)),
            collect(array_map(fn ($header) => (['name' => 'Workbook Child', 'npc_id' => $npc->real_name, 'parent_quest_id' => 'Workbook Parent'][$header] ?? null), self::HEADERS)),
        ]);

        $sheet = new QuestsSheet;
        $sheet->collection($rows);

        $this->assertTrue($sheet->wasSuccessful());
        $parent = Quest::where('name', 'Workbook Parent')->first();
        $child = Quest::where('name', 'Workbook Child')->first();
        $this->assertSame($parent->id, $child->parent_quest_id);
        $this->assertTrue((bool) $parent->is_parent);
    }

    public function test_two_new_quests_where_one_requires_the_other(): void
    {
        $npc = $this->createNpc();

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => (['name' => 'Workbook Prerequisite', 'npc_id' => $npc->real_name][$header] ?? null), self::HEADERS)),
            collect(array_map(fn ($header) => (['name' => 'Workbook Dependent', 'npc_id' => $npc->real_name, 'required_quest_id' => 'Workbook Prerequisite'][$header] ?? null), self::HEADERS)),
        ]);

        $sheet = new QuestsSheet;
        $sheet->collection($rows);

        $this->assertTrue($sheet->wasSuccessful());
        $prerequisite = Quest::where('name', 'Workbook Prerequisite')->first();
        $dependent = Quest::where('name', 'Workbook Dependent')->first();
        $this->assertSame($prerequisite->id, $dependent->required_quest_id);
    }

    public function test_required_chain_names_referring_to_workbook_local_quests(): void
    {
        $npc = $this->createNpc();

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => (['name' => 'Chain Member One', 'npc_id' => $npc->real_name][$header] ?? null), self::HEADERS)),
            collect(array_map(fn ($header) => (['name' => 'Chain Member Two', 'npc_id' => $npc->real_name][$header] ?? null), self::HEADERS)),
            collect(array_map(fn ($header) => (['name' => 'Chain Owner', 'npc_id' => $npc->real_name, 'required_quest_chain' => 'Chain Member One,Chain Member Two'][$header] ?? null), self::HEADERS)),
        ]);

        $sheet = new QuestsSheet;
        $sheet->collection($rows);

        $this->assertTrue($sheet->wasSuccessful());
        $memberOne = Quest::where('name', 'Chain Member One')->first();
        $memberTwo = Quest::where('name', 'Chain Member Two')->first();
        $owner = Quest::where('name', 'Chain Owner')->first();
        $this->assertSame([$memberOne->id, $memberTwo->id], $owner->required_quest_chain);
    }

    public function test_parent_cycle_is_rejected(): void
    {
        $npc = $this->createNpc();

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => (['name' => 'Cycle Parent', 'npc_id' => $npc->real_name, 'parent_quest_id' => 'Cycle Child'][$header] ?? null), self::HEADERS)),
            collect(array_map(fn ($header) => (['name' => 'Cycle Child', 'npc_id' => $npc->real_name, 'parent_quest_id' => 'Cycle Parent'][$header] ?? null), self::HEADERS)),
        ]);

        $sheet = new QuestsSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertNotNull($sheet->validationError());
        $this->assertSame(0, Quest::count());
    }

    public function test_prerequisite_cycle_is_rejected(): void
    {
        $npc = $this->createNpc();

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => (['name' => 'Prereq A', 'npc_id' => $npc->real_name, 'required_quest_id' => 'Prereq B'][$header] ?? null), self::HEADERS)),
            collect(array_map(fn ($header) => (['name' => 'Prereq B', 'npc_id' => $npc->real_name, 'required_quest_id' => 'Prereq A'][$header] ?? null), self::HEADERS)),
        ]);

        $sheet = new QuestsSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertSame(0, Quest::count());
    }

    public function test_required_chain_cycle_is_rejected(): void
    {
        $npc = $this->createNpc();

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => (['name' => 'Chain A', 'npc_id' => $npc->real_name, 'required_quest_chain' => 'Chain B'][$header] ?? null), self::HEADERS)),
            collect(array_map(fn ($header) => (['name' => 'Chain B', 'npc_id' => $npc->real_name, 'required_quest_chain' => 'Chain A'][$header] ?? null), self::HEADERS)),
        ]);

        $sheet = new QuestsSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertSame(0, Quest::count());
    }

    public function test_duplicate_quest_names_are_rejected(): void
    {
        $npc = $this->createNpc();

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => (['name' => 'Duplicate Quest', 'npc_id' => $npc->real_name][$header] ?? null), self::HEADERS)),
            collect(array_map(fn ($header) => (['name' => 'Duplicate Quest', 'npc_id' => $npc->real_name][$header] ?? null), self::HEADERS)),
        ]);

        $sheet = new QuestsSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertSame(0, Quest::count());
    }

    public function test_invalid_npc_is_rejected(): void
    {
        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => (['name' => 'Invalid Npc Quest', 'npc_id' => 'Does Not Exist'][$header] ?? null), self::HEADERS)),
        ]);

        $sheet = new QuestsSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertSame(0, Quest::count());
    }

    public function test_invalid_item_is_rejected(): void
    {
        $npc = $this->createNpc();

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => (['name' => 'Invalid Item Quest', 'npc_id' => $npc->real_name, 'item_id' => 'Does Not Exist'][$header] ?? null), self::HEADERS)),
        ]);

        $sheet = new QuestsSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertSame(0, Quest::count());
    }

    public function test_invalid_map_is_rejected(): void
    {
        $npc = $this->createNpc();

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => (['name' => 'Invalid Map Quest', 'npc_id' => $npc->real_name, 'access_to_map_id' => 'Does Not Exist'][$header] ?? null), self::HEADERS)),
        ]);

        $sheet = new QuestsSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertSame(0, Quest::count());
    }

    public function test_invalid_raid_is_rejected(): void
    {
        $npc = $this->createNpc();

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => (['name' => 'Invalid Raid Quest', 'npc_id' => $npc->real_name, 'raid_id' => 'Does Not Exist'][$header] ?? null), self::HEADERS)),
        ]);

        $sheet = new QuestsSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertSame(0, Quest::count());
    }

    public function test_invalid_passive_is_rejected(): void
    {
        $npc = $this->createNpc();

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => (['name' => 'Invalid Passive Quest', 'npc_id' => $npc->real_name, 'unlocks_passive_id' => 'Does Not Exist'][$header] ?? null), self::HEADERS)),
        ]);

        $sheet = new QuestsSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertSame(0, Quest::count());
    }

    public function test_validation_failure_produces_zero_writes(): void
    {
        $npc = $this->createNpc();

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => (['name' => 'Valid First Row', 'npc_id' => $npc->real_name][$header] ?? null), self::HEADERS)),
            collect(array_map(fn ($header) => (['name' => 'Invalid Second Row', 'npc_id' => 'Does Not Exist'][$header] ?? null), self::HEADERS)),
        ]);

        $sheet = new QuestsSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertSame(0, Quest::count());
    }

    public function test_successful_workbook_writes_only_after_full_validation(): void
    {
        $npc = $this->createNpc();

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => (['name' => 'Successful Row One', 'npc_id' => $npc->real_name][$header] ?? null), self::HEADERS)),
            collect(array_map(fn ($header) => (['name' => 'Successful Row Two', 'npc_id' => $npc->real_name][$header] ?? null), self::HEADERS)),
        ]);

        $sheet = new QuestsSheet;
        $sheet->collection($rows);

        $this->assertTrue($sheet->wasSuccessful());
        $this->assertSame(2, Quest::count());
    }

    public function test_negative_reincarnated_times_is_rejected(): void
    {
        $npc = $this->createNpc();

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => (['name' => 'Negative Reincarnated', 'npc_id' => $npc->real_name, 'reincarnated_times' => -1][$header] ?? null), self::HEADERS)),
        ]);

        $sheet = new QuestsSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertSame(0, Quest::count());
    }

    public function test_negative_gold_cost_is_rejected(): void
    {
        $npc = $this->createNpc();

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => (['name' => 'Negative Gold Cost', 'npc_id' => $npc->real_name, 'gold_cost' => -1][$header] ?? null), self::HEADERS)),
        ]);

        $sheet = new QuestsSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertSame(0, Quest::count());
    }

    public function test_negative_gold_dust_cost_is_rejected(): void
    {
        $npc = $this->createNpc();

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => (['name' => 'Negative Gold Dust Cost', 'npc_id' => $npc->real_name, 'gold_dust_cost' => -1][$header] ?? null), self::HEADERS)),
        ]);

        $sheet = new QuestsSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertSame(0, Quest::count());
    }

    public function test_negative_shard_cost_is_rejected(): void
    {
        $npc = $this->createNpc();

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => (['name' => 'Negative Shard Cost', 'npc_id' => $npc->real_name, 'shard_cost' => -1][$header] ?? null), self::HEADERS)),
        ]);

        $sheet = new QuestsSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertSame(0, Quest::count());
    }

    public function test_negative_copper_coin_cost_is_rejected(): void
    {
        $npc = $this->createNpc();

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => (['name' => 'Negative Copper Coin Cost', 'npc_id' => $npc->real_name, 'copper_coin_cost' => -1][$header] ?? null), self::HEADERS)),
        ]);

        $sheet = new QuestsSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertSame(0, Quest::count());
    }

    public function test_negative_required_faction_level_is_rejected(): void
    {
        $npc = $this->createNpc();

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => (['name' => 'Negative Faction Level', 'npc_id' => $npc->real_name, 'required_faction_level' => -1][$header] ?? null), self::HEADERS)),
        ]);

        $sheet = new QuestsSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertSame(0, Quest::count());
    }

    public function test_negative_required_fame_level_is_rejected(): void
    {
        $npc = $this->createNpc();

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => (['name' => 'Negative Fame Level', 'npc_id' => $npc->real_name, 'required_fame_level' => -1][$header] ?? null), self::HEADERS)),
        ]);

        $sheet = new QuestsSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertSame(0, Quest::count());
    }

    public function test_negative_reward_gold_is_rejected(): void
    {
        $npc = $this->createNpc();

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => (['name' => 'Negative Reward Gold', 'npc_id' => $npc->real_name, 'reward_gold' => -1][$header] ?? null), self::HEADERS)),
        ]);

        $sheet = new QuestsSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertSame(0, Quest::count());
    }

    public function test_negative_reward_gold_dust_is_rejected(): void
    {
        $npc = $this->createNpc();

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => (['name' => 'Negative Reward Gold Dust', 'npc_id' => $npc->real_name, 'reward_gold_dust' => -1][$header] ?? null), self::HEADERS)),
        ]);

        $sheet = new QuestsSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertSame(0, Quest::count());
    }

    public function test_negative_reward_shards_is_rejected(): void
    {
        $npc = $this->createNpc();

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => (['name' => 'Negative Reward Shards', 'npc_id' => $npc->real_name, 'reward_shards' => -1][$header] ?? null), self::HEADERS)),
        ]);

        $sheet = new QuestsSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertSame(0, Quest::count());
    }

    public function test_negative_reward_xp_is_rejected(): void
    {
        $npc = $this->createNpc();

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => (['name' => 'Negative Reward Xp', 'npc_id' => $npc->real_name, 'reward_xp' => -1][$header] ?? null), self::HEADERS)),
        ]);

        $sheet = new QuestsSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertSame(0, Quest::count());
    }

    public function test_invalid_event_domain_is_rejected(): void
    {
        $npc = $this->createNpc();

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => (['name' => 'Invalid Event Quest', 'npc_id' => $npc->real_name, 'only_for_event' => 999999][$header] ?? null), self::HEADERS)),
        ]);

        $sheet = new QuestsSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertSame(0, Quest::count());
    }

    public function test_invalid_skill_type_is_rejected(): void
    {
        $npc = $this->createNpc();

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => (['name' => 'Invalid Skill Type Quest', 'npc_id' => $npc->real_name, 'unlocks_skill' => true, 'unlocks_skill_type' => 999999][$header] ?? null), self::HEADERS)),
        ]);

        $sheet = new QuestsSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertSame(0, Quest::count());
    }

    public function test_invalid_feature_type_is_rejected(): void
    {
        $npc = $this->createNpc();

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => (['name' => 'Invalid Feature Quest', 'npc_id' => $npc->real_name, 'unlocks_feature' => 999999][$header] ?? null), self::HEADERS)),
        ]);

        $sheet = new QuestsSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertSame(0, Quest::count());
    }

    public function test_invalid_oversized_name_is_rejected(): void
    {
        $npc = $this->createNpc();

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => ([
                'name' => str_repeat('A', 256),
                'npc_id' => $npc->real_name,
            ][$header] ?? null), self::HEADERS)),
        ]);

        $sheet = new QuestsSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertSame(0, Quest::count());
    }

    public function test_malformed_unlocks_skill_value_does_not_become_true(): void
    {
        $npc = $this->createNpc();

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => (['name' => 'Malformed Boolean Quest', 'npc_id' => $npc->real_name, 'unlocks_skill' => 'maybe'][$header] ?? null), self::HEADERS)),
        ]);

        $sheet = new QuestsSheet;
        $sheet->collection($rows);

        $this->assertTrue($sheet->wasSuccessful());
        $this->assertFalse((bool) Quest::where('name', 'Malformed Boolean Quest')->first()->unlocks_skill);
    }

    public function test_stale_imported_is_parent_true_with_no_children_ends_false(): void
    {
        $npc = $this->createNpc();

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => (['name' => 'Stale Parent Flag Quest', 'npc_id' => $npc->real_name, 'is_parent' => true][$header] ?? null), self::HEADERS)),
        ]);

        $sheet = new QuestsSheet;
        $sheet->collection($rows);

        $this->assertTrue($sheet->wasSuccessful());
        $this->assertFalse((bool) Quest::where('name', 'Stale Parent Flag Quest')->first()->is_parent);
    }

    public function test_no_child_quest_ends_is_parent_false_regardless_of_imported_value(): void
    {
        $npc = $this->createNpc();

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => (['name' => 'Childless Quest', 'npc_id' => $npc->real_name, 'is_parent' => false][$header] ?? null), self::HEADERS)),
        ]);

        $sheet = new QuestsSheet;
        $sheet->collection($rows);

        $this->assertTrue($sheet->wasSuccessful());
        $this->assertFalse((bool) Quest::where('name', 'Childless Quest')->first()->is_parent);
    }

    public function test_parent_chain_quest_id_compatibility_round_trip(): void
    {
        $npc = $this->createNpc();

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => (['name' => 'Compat Quest', 'npc_id' => $npc->real_name, 'parent_chain_quest_id' => 42][$header] ?? null), self::HEADERS)),
        ]);

        $sheet = new QuestsSheet;
        $sheet->collection($rows);

        $this->assertTrue($sheet->wasSuccessful());
        $this->assertSame(42, Quest::where('name', 'Compat Quest')->first()->parent_chain_quest_id);
    }
}
