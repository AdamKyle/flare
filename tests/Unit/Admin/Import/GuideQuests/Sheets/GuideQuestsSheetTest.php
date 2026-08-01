<?php

namespace Tests\Unit\Admin\Import\GuideQuests\Sheets;

use App\Admin\Import\GuideQuests\Sheets\GuideQuestsSheet;
use App\Flare\Items\Values\AlchemyItemType;
use App\Flare\Models\GuideQuest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Tests\Traits\CreateGuideQuest;
use Tests\Traits\CreateItem;

class GuideQuestsSheetTest extends TestCase
{
    use CreateGuideQuest, CreateItem, RefreshDatabase;

    public function test_import_creates_guide_quest_with_batch_crafting_requirement(): void
    {
        (new GuideQuestsSheet)->collection(new Collection([
            new Collection([
                'id',
                'name',
                'intro_text',
                'instructions',
                'desktop_instructions',
                'mobile_instructions',
                'required_game_map_id',
                'be_on_game_map',
                'required_skill',
                'required_skill_level',
                'required_secondary_skill',
                'required_secondary_skill_level',
                'required_passive_skill',
                'required_passive_level',
                'required_faction_id',
                'required_faction_level',
                'required_quest_item_id',
                'secondary_quest_item_id',
                'required_quest_id',
                'required_kingdom_building_id',
                'required_kingdom_building_level',
                'parent_id',
                'required_batch_crafting_type',
                'required_batch_crafting_hours',
                'xp_reward',
            ]),
            new Collection([
                null,
                'Batch Crafting Import Quest',
                'Intro text',
                'Instructions',
                'Desktop',
                'Mobile',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                'craft',
                3,
                100,
            ]),
        ]));

        $guideQuest = GuideQuest::where('name', 'Batch Crafting Import Quest')->first();

        $this->assertSame('craft', $guideQuest->required_batch_crafting_type);
        $this->assertSame(3, $guideQuest->required_batch_crafting_hours);
    }

    public function test_import_creates_guide_quest_with_event_goal_craft_and_enchant_requirements(): void
    {
        (new GuideQuestsSheet)->collection(new Collection([
            new Collection([
                'id',
                'name',
                'intro_text',
                'instructions',
                'desktop_instructions',
                'mobile_instructions',
                'required_game_map_id',
                'be_on_game_map',
                'required_skill',
                'required_skill_level',
                'required_secondary_skill',
                'required_secondary_skill_level',
                'required_passive_skill',
                'required_passive_level',
                'required_faction_id',
                'required_faction_level',
                'required_quest_item_id',
                'secondary_quest_item_id',
                'required_quest_id',
                'required_kingdom_building_id',
                'required_kingdom_building_level',
                'parent_id',
                'required_event_goal_crafting_participation',
                'required_event_goal_enchanting_participation',
                'xp_reward',
            ]),
            new Collection([
                null,
                'Event Goal Participation Import Quest',
                'Intro text',
                'Instructions',
                'Desktop',
                'Mobile',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                12,
                13,
                100,
            ]),
        ]));

        $guideQuest = GuideQuest::where('name', 'Event Goal Participation Import Quest')->first();

        $this->assertSame(12, $guideQuest->required_event_goal_crafting_participation);
        $this->assertSame(13, $guideQuest->required_event_goal_enchanting_participation);
    }

    public function test_import_updates_guide_quest_with_batch_crafting_requirement(): void
    {
        $this->createGuideQuest([
            'name' => 'Batch Crafting Update Quest',
            'required_batch_crafting_type' => 'craft',
            'required_batch_crafting_hours' => 2,
        ]);

        (new GuideQuestsSheet)->collection(new Collection([
            new Collection([
                'id',
                'name',
                'intro_text',
                'instructions',
                'desktop_instructions',
                'mobile_instructions',
                'required_game_map_id',
                'be_on_game_map',
                'required_skill',
                'required_skill_level',
                'required_secondary_skill',
                'required_secondary_skill_level',
                'required_passive_skill',
                'required_passive_level',
                'required_faction_id',
                'required_faction_level',
                'required_quest_item_id',
                'secondary_quest_item_id',
                'required_quest_id',
                'required_kingdom_building_id',
                'required_kingdom_building_level',
                'parent_id',
                'required_batch_crafting_type',
                'required_batch_crafting_hours',
                'xp_reward',
            ]),
            new Collection([
                null,
                'Batch Crafting Update Quest',
                'Intro text',
                'Instructions',
                'Desktop',
                'Mobile',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                'trinketry',
                4,
                100,
            ]),
        ]));

        $guideQuest = GuideQuest::where('name', 'Batch Crafting Update Quest')->first();

        $this->assertSame('trinketry', $guideQuest->required_batch_crafting_type);
        $this->assertSame(4, $guideQuest->required_batch_crafting_hours);
    }

    public function test_invalid_holy_oils_import_clears_batch_crafting_requirement_fields(): void
    {
        (new GuideQuestsSheet)->collection(new Collection([
            new Collection([
                'id',
                'name',
                'intro_text',
                'instructions',
                'desktop_instructions',
                'mobile_instructions',
                'required_game_map_id',
                'be_on_game_map',
                'required_skill',
                'required_skill_level',
                'required_secondary_skill',
                'required_secondary_skill_level',
                'required_passive_skill',
                'required_passive_level',
                'required_faction_id',
                'required_faction_level',
                'required_quest_item_id',
                'secondary_quest_item_id',
                'required_quest_id',
                'required_kingdom_building_id',
                'required_kingdom_building_level',
                'parent_id',
                'required_batch_crafting_type',
                'required_batch_crafting_hours',
                'xp_reward',
            ]),
            new Collection([
                null,
                'Invalid Batch Crafting Import Quest',
                'Intro text',
                'Instructions',
                'Desktop',
                'Mobile',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                'holy_oils',
                3,
                100,
            ]),
        ]));

        $guideQuest = GuideQuest::where('name', 'Invalid Batch Crafting Import Quest')->first();

        $this->assertNull($guideQuest->required_batch_crafting_type);
        $this->assertNull($guideQuest->required_batch_crafting_hours);
    }

    public function test_import_creates_required_batch_crafted_items(): void
    {
        $dagger = $this->createItem(['name' => 'Imported Iron Dagger', 'type' => 'dagger', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);

        (new GuideQuestsSheet)->collection(new Collection([
            new Collection([
                'id',
                'name',
                'intro_text',
                'instructions',
                'desktop_instructions',
                'mobile_instructions',
                'required_game_map_id',
                'be_on_game_map',
                'required_skill',
                'required_skill_level',
                'required_secondary_skill',
                'required_secondary_skill_level',
                'required_passive_skill',
                'required_passive_level',
                'required_faction_id',
                'required_faction_level',
                'required_quest_item_id',
                'secondary_quest_item_id',
                'required_quest_id',
                'required_kingdom_building_id',
                'required_kingdom_building_level',
                'parent_id',
                'required_batch_crafted_item_1_name',
                'required_batch_crafted_item_1_type',
                'required_batch_crafted_item_1_amount',
                'required_batch_crafted_item_1_must_be_enchanted',
                'xp_reward',
            ]),
            new Collection([
                null,
                'Batch Crafted Items Import Quest',
                'Intro text',
                'Instructions',
                'Desktop',
                'Mobile',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                'Imported Iron Dagger',
                'dagger',
                50,
                0,
                100,
            ]),
        ]));

        $guideQuest = GuideQuest::where('name', 'Batch Crafted Items Import Quest')->first();

        $this->assertSame($dagger->id, $guideQuest->required_batch_crafted_items[0]['item_id']);
        $this->assertSame(50, $guideQuest->required_batch_crafted_items[0]['amount']);
        $this->assertFalse($guideQuest->required_batch_crafted_items[0]['must_be_enchanted']);
    }

    public function test_import_updates_required_batch_crafted_items(): void
    {
        $dagger = $this->createItem(['name' => 'Original Imported Dagger', 'type' => 'dagger', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $helmet = $this->createItem(['name' => 'Updated Imported Helmet', 'type' => 'helmet', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $this->createGuideQuest([
            'name' => 'Batch Crafted Items Update Quest',
            'required_batch_crafted_items' => [
                ['item_id' => $dagger->id, 'amount' => 2, 'must_be_enchanted' => false],
            ],
        ]);

        (new GuideQuestsSheet)->collection(new Collection([
            new Collection([
                'id',
                'name',
                'intro_text',
                'instructions',
                'desktop_instructions',
                'mobile_instructions',
                'required_game_map_id',
                'be_on_game_map',
                'required_skill',
                'required_skill_level',
                'required_secondary_skill',
                'required_secondary_skill_level',
                'required_passive_skill',
                'required_passive_level',
                'required_faction_id',
                'required_faction_level',
                'required_quest_item_id',
                'secondary_quest_item_id',
                'required_quest_id',
                'required_kingdom_building_id',
                'required_kingdom_building_level',
                'parent_id',
                'required_batch_crafted_item_1_name',
                'required_batch_crafted_item_1_type',
                'required_batch_crafted_item_1_amount',
                'required_batch_crafted_item_1_must_be_enchanted',
                'xp_reward',
            ]),
            new Collection([
                null,
                'Batch Crafted Items Update Quest',
                'Intro text',
                'Instructions',
                'Desktop',
                'Mobile',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                'Updated Imported Helmet',
                'helmet',
                10,
                1,
                100,
            ]),
        ]));

        $guideQuest = GuideQuest::where('name', 'Batch Crafted Items Update Quest')->first();

        $this->assertSame($helmet->id, $guideQuest->required_batch_crafted_items[0]['item_id']);
        $this->assertSame(10, $guideQuest->required_batch_crafted_items[0]['amount']);
        $this->assertTrue($guideQuest->required_batch_crafted_items[0]['must_be_enchanted']);
    }

    public function test_import_skips_invalid_batch_crafted_item_rows(): void
    {
        $dagger = $this->createItem(['name' => 'Valid Imported Dagger', 'type' => 'dagger', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);

        (new GuideQuestsSheet)->collection(new Collection([
            new Collection([
                'id',
                'name',
                'intro_text',
                'instructions',
                'desktop_instructions',
                'mobile_instructions',
                'required_game_map_id',
                'be_on_game_map',
                'required_skill',
                'required_skill_level',
                'required_secondary_skill',
                'required_secondary_skill_level',
                'required_passive_skill',
                'required_passive_level',
                'required_faction_id',
                'required_faction_level',
                'required_quest_item_id',
                'secondary_quest_item_id',
                'required_quest_id',
                'required_kingdom_building_id',
                'required_kingdom_building_level',
                'parent_id',
                'required_batch_crafted_item_1_name',
                'required_batch_crafted_item_1_type',
                'required_batch_crafted_item_1_amount',
                'required_batch_crafted_item_1_must_be_enchanted',
                'required_batch_crafted_item_2_name',
                'required_batch_crafted_item_2_type',
                'required_batch_crafted_item_2_amount',
                'required_batch_crafted_item_2_must_be_enchanted',
                'xp_reward',
            ]),
            new Collection([
                null,
                'Batch Crafted Items Skip Invalid Quest',
                'Intro text',
                'Instructions',
                'Desktop',
                'Mobile',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                'Valid Imported Dagger',
                'dagger',
                5,
                0,
                'Missing Imported Helmet',
                'helmet',
                3,
                1,
                100,
            ]),
        ]));

        $guideQuest = GuideQuest::where('name', 'Batch Crafted Items Skip Invalid Quest')->first();

        $this->assertCount(1, $guideQuest->required_batch_crafted_items);
        $this->assertSame($dagger->id, $guideQuest->required_batch_crafted_items[0]['item_id']);
        $this->assertSame(5, $guideQuest->required_batch_crafted_items[0]['amount']);
    }

    public function test_import_sets_required_batch_crafted_items_to_null_when_all_rows_are_invalid_or_blank(): void
    {
        (new GuideQuestsSheet)->collection(new Collection([
            new Collection([
                'id',
                'name',
                'intro_text',
                'instructions',
                'desktop_instructions',
                'mobile_instructions',
                'required_game_map_id',
                'be_on_game_map',
                'required_skill',
                'required_skill_level',
                'required_secondary_skill',
                'required_secondary_skill_level',
                'required_passive_skill',
                'required_passive_level',
                'required_faction_id',
                'required_faction_level',
                'required_quest_item_id',
                'secondary_quest_item_id',
                'required_quest_id',
                'required_kingdom_building_id',
                'required_kingdom_building_level',
                'parent_id',
                'required_batch_crafted_item_1_name',
                'required_batch_crafted_item_1_type',
                'required_batch_crafted_item_1_amount',
                'required_batch_crafted_item_1_must_be_enchanted',
                'required_batch_crafted_item_2_name',
                'required_batch_crafted_item_2_type',
                'required_batch_crafted_item_2_amount',
                'required_batch_crafted_item_2_must_be_enchanted',
                'xp_reward',
            ]),
            new Collection([
                null,
                'Batch Crafted Items All Invalid Quest',
                'Intro text',
                'Instructions',
                'Desktop',
                'Mobile',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                'Missing Imported Helmet',
                'helmet',
                0,
                1,
                '',
                '',
                '',
                '',
                100,
            ]),
        ]));

        $guideQuest = GuideQuest::where('name', 'Batch Crafted Items All Invalid Quest')->first();

        $this->assertNull($guideQuest->required_batch_crafted_items);
    }

    public function test_import_preserves_must_be_enchanted_as_two_enchant_requirement_flag(): void
    {
        $helmet = $this->createItem(['name' => 'Two Enchant Imported Helmet', 'type' => 'helmet', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);

        (new GuideQuestsSheet)->collection(new Collection([
            new Collection([
                'id',
                'name',
                'intro_text',
                'instructions',
                'desktop_instructions',
                'mobile_instructions',
                'required_game_map_id',
                'be_on_game_map',
                'required_skill',
                'required_skill_level',
                'required_secondary_skill',
                'required_secondary_skill_level',
                'required_passive_skill',
                'required_passive_level',
                'required_faction_id',
                'required_faction_level',
                'required_quest_item_id',
                'secondary_quest_item_id',
                'required_quest_id',
                'required_kingdom_building_id',
                'required_kingdom_building_level',
                'parent_id',
                'required_batch_crafted_item_1_name',
                'required_batch_crafted_item_1_type',
                'required_batch_crafted_item_1_amount',
                'required_batch_crafted_item_1_must_be_enchanted',
                'xp_reward',
            ]),
            new Collection([
                null,
                'Two Enchant Import Quest',
                'Intro text',
                'Instructions',
                'Desktop',
                'Mobile',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                'Two Enchant Imported Helmet',
                'helmet',
                3,
                'true',
                100,
            ]),
        ]));

        $guideQuest = GuideQuest::where('name', 'Two Enchant Import Quest')->first();

        $this->assertSame($helmet->id, $guideQuest->required_batch_crafted_items[0]['item_id']);
        $this->assertSame(3, $guideQuest->required_batch_crafted_items[0]['amount']);
        $this->assertTrue($guideQuest->required_batch_crafted_items[0]['must_be_enchanted']);
    }

    public function test_import_does_not_require_or_store_specific_enchantment_ids(): void
    {
        $helmet = $this->createItem(['name' => 'No Affix Id Imported Helmet', 'type' => 'helmet', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);

        (new GuideQuestsSheet)->collection(new Collection([
            new Collection([
                'id',
                'name',
                'intro_text',
                'instructions',
                'desktop_instructions',
                'mobile_instructions',
                'required_game_map_id',
                'be_on_game_map',
                'required_skill',
                'required_skill_level',
                'required_secondary_skill',
                'required_secondary_skill_level',
                'required_passive_skill',
                'required_passive_level',
                'required_faction_id',
                'required_faction_level',
                'required_quest_item_id',
                'secondary_quest_item_id',
                'required_quest_id',
                'required_kingdom_building_id',
                'required_kingdom_building_level',
                'parent_id',
                'required_batch_crafted_item_1_name',
                'required_batch_crafted_item_1_type',
                'required_batch_crafted_item_1_amount',
                'required_batch_crafted_item_1_must_be_enchanted',
                'xp_reward',
            ]),
            new Collection([
                null,
                'No Affix Id Import Quest',
                'Intro text',
                'Instructions',
                'Desktop',
                'Mobile',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                'No Affix Id Imported Helmet',
                'helmet',
                1,
                1,
                100,
            ]),
        ]));

        $guideQuest = GuideQuest::where('name', 'No Affix Id Import Quest')->first();

        $this->assertSame($helmet->id, $guideQuest->required_batch_crafted_items[0]['item_id']);
        $this->assertArrayNotHasKey('item_prefix_id', $guideQuest->required_batch_crafted_items[0]);
        $this->assertArrayNotHasKey('item_suffix_id', $guideQuest->required_batch_crafted_items[0]);
    }

    public function test_import_creates_one_inventory_row_and_one_alchemy_bag_row(): void
    {
        $dagger = $this->createItem(['name' => 'Mixed Imported Dagger', 'type' => 'dagger', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $potion = $this->createItem(['name' => 'Mixed Imported Potion', 'type' => 'alchemy', 'alchemy_type' => AlchemyItemType::INCREASE_STATS->value]);

        (new GuideQuestsSheet)->collection(new Collection([
            new Collection([
                'name',
                'intro_text',
                'instructions',
                'desktop_instructions',
                'mobile_instructions',
                'required_game_map_id',
                'be_on_game_map',
                'required_skill',
                'required_skill_level',
                'required_secondary_skill',
                'required_secondary_skill_level',
                'required_passive_skill',
                'required_passive_level',
                'required_faction_id',
                'required_faction_level',
                'required_quest_item_id',
                'secondary_quest_item_id',
                'required_quest_id',
                'required_kingdom_building_id',
                'required_kingdom_building_level',
                'parent_id',
                'required_item_1_source',
                'required_item_1_name',
                'required_item_1_type',
                'required_item_1_amount',
                'required_item_1_must_be_enchanted',
                'required_item_2_source',
                'required_item_2_name',
                'required_item_2_type',
                'required_item_2_amount',
                'required_item_2_must_be_enchanted',
                'xp_reward',
            ]),
            new Collection([
                'Mixed Import Quest',
                'Intro text',
                'Instructions',
                'Desktop',
                'Mobile',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                'inventory',
                'Mixed Imported Dagger',
                'Daggers',
                2,
                0,
                'alchemy_bag',
                'Mixed Imported Potion',
                'Increases Stats',
                5,
                1,
                100,
            ]),
        ]));

        $guideQuest = GuideQuest::where('name', 'Mixed Import Quest')->first();

        $this->assertSame('inventory', $guideQuest->required_batch_crafted_items[0]['source']);
        $this->assertSame($dagger->id, $guideQuest->required_batch_crafted_items[0]['item_id']);
        $this->assertSame(2, $guideQuest->required_batch_crafted_items[0]['amount']);
        $this->assertSame('alchemy_bag', $guideQuest->required_batch_crafted_items[1]['source']);
        $this->assertSame($potion->id, $guideQuest->required_batch_crafted_items[1]['item_id']);
        $this->assertSame(5, $guideQuest->required_batch_crafted_items[1]['amount']);
    }

    public function test_import_forces_alchemy_row_must_be_enchanted_to_false(): void
    {
        $potion = $this->createItem(['name' => 'Forced Imported Potion', 'type' => 'alchemy', 'alchemy_type' => AlchemyItemType::INCREASE_STATS->value]);

        (new GuideQuestsSheet)->collection(new Collection([
            new Collection(['name', 'intro_text', 'instructions', 'desktop_instructions', 'mobile_instructions', 'required_game_map_id', 'be_on_game_map', 'required_skill', 'required_skill_level', 'required_secondary_skill', 'required_secondary_skill_level', 'required_passive_skill', 'required_passive_level', 'required_faction_id', 'required_faction_level', 'required_quest_item_id', 'secondary_quest_item_id', 'required_quest_id', 'required_kingdom_building_id', 'required_kingdom_building_level', 'parent_id', 'required_item_1_source', 'required_item_1_name', 'required_item_1_type', 'required_item_1_amount', 'required_item_1_must_be_enchanted', 'xp_reward']),
            new Collection(['Forced Alchemy Import Quest', 'Intro text', 'Instructions', 'Desktop', 'Mobile', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'alchemy_bag', 'Forced Imported Potion', 'Increases Stats', 2, 1, 100]),
        ]));

        $guideQuest = GuideQuest::where('name', 'Forced Alchemy Import Quest')->first();

        $this->assertSame($potion->id, $guideQuest->required_batch_crafted_items[0]['item_id']);
        $this->assertFalse($guideQuest->required_batch_crafted_items[0]['must_be_enchanted']);
    }

    public function test_import_skips_invalid_alchemy_rows_where_item_type_is_not_alchemy(): void
    {
        $dagger = $this->createItem(['name' => 'Invalid Alchemy Dagger', 'type' => 'dagger', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);

        (new GuideQuestsSheet)->collection(new Collection([
            new Collection(['name', 'intro_text', 'instructions', 'desktop_instructions', 'mobile_instructions', 'required_game_map_id', 'be_on_game_map', 'required_skill', 'required_skill_level', 'required_secondary_skill', 'required_secondary_skill_level', 'required_passive_skill', 'required_passive_level', 'required_faction_id', 'required_faction_level', 'required_quest_item_id', 'secondary_quest_item_id', 'required_quest_id', 'required_kingdom_building_id', 'required_kingdom_building_level', 'parent_id', 'required_item_1_source', 'required_item_1_name', 'required_item_1_type', 'required_item_1_amount', 'required_item_1_must_be_enchanted', 'xp_reward']),
            new Collection(['Invalid Alchemy Source Import Quest', 'Intro text', 'Instructions', 'Desktop', 'Mobile', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'alchemy_bag', 'Invalid Alchemy Dagger', 'Daggers', 1, 0, 100]),
        ]));

        $guideQuest = GuideQuest::where('name', 'Invalid Alchemy Source Import Quest')->first();

        $this->assertSame($dagger->type, 'dagger');
        $this->assertNull($guideQuest->required_batch_crafted_items);
    }

    public function test_import_skips_invalid_inventory_rows_where_item_type_is_alchemy(): void
    {
        $potion = $this->createItem(['name' => 'Invalid Inventory Potion', 'type' => 'alchemy', 'alchemy_type' => AlchemyItemType::INCREASE_STATS->value]);

        (new GuideQuestsSheet)->collection(new Collection([
            new Collection(['name', 'intro_text', 'instructions', 'desktop_instructions', 'mobile_instructions', 'required_game_map_id', 'be_on_game_map', 'required_skill', 'required_skill_level', 'required_secondary_skill', 'required_secondary_skill_level', 'required_passive_skill', 'required_passive_level', 'required_faction_id', 'required_faction_level', 'required_quest_item_id', 'secondary_quest_item_id', 'required_quest_id', 'required_kingdom_building_id', 'required_kingdom_building_level', 'parent_id', 'required_item_1_source', 'required_item_1_name', 'required_item_1_type', 'required_item_1_amount', 'required_item_1_must_be_enchanted', 'xp_reward']),
            new Collection(['Invalid Inventory Source Import Quest', 'Intro text', 'Instructions', 'Desktop', 'Mobile', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'inventory', 'Invalid Inventory Potion', 'Increases Stats', 1, 0, 100]),
        ]));

        $guideQuest = GuideQuest::where('name', 'Invalid Inventory Source Import Quest')->first();

        $this->assertSame($potion->type, 'alchemy');
        $this->assertNull($guideQuest->required_batch_crafted_items);
    }

    public function test_import_ignores_rows_three_through_five_if_old_columns_are_present(): void
    {
        $dagger = $this->createItem(['name' => 'Ignored Rows Dagger', 'type' => 'dagger', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $helmet = $this->createItem(['name' => 'Ignored Rows Helmet', 'type' => 'helmet', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $ring = $this->createItem(['name' => 'Ignored Rows Ring', 'type' => 'ring', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);

        (new GuideQuestsSheet)->collection(new Collection([
            new Collection(['name', 'intro_text', 'instructions', 'desktop_instructions', 'mobile_instructions', 'required_game_map_id', 'be_on_game_map', 'required_skill', 'required_skill_level', 'required_secondary_skill', 'required_secondary_skill_level', 'required_passive_skill', 'required_passive_level', 'required_faction_id', 'required_faction_level', 'required_quest_item_id', 'secondary_quest_item_id', 'required_quest_id', 'required_kingdom_building_id', 'required_kingdom_building_level', 'parent_id', 'required_batch_crafted_item_1_name', 'required_batch_crafted_item_1_type', 'required_batch_crafted_item_1_amount', 'required_batch_crafted_item_1_must_be_enchanted', 'required_batch_crafted_item_2_name', 'required_batch_crafted_item_2_type', 'required_batch_crafted_item_2_amount', 'required_batch_crafted_item_2_must_be_enchanted', 'required_batch_crafted_item_3_name', 'required_batch_crafted_item_3_type', 'required_batch_crafted_item_3_amount', 'required_batch_crafted_item_3_must_be_enchanted', 'xp_reward']),
            new Collection(['Ignore Old Extra Rows Import Quest', 'Intro text', 'Instructions', 'Desktop', 'Mobile', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'Ignored Rows Dagger', 'dagger', 1, 0, 'Ignored Rows Helmet', 'helmet', 1, 0, 'Ignored Rows Ring', 'ring', 1, 0, 100]),
        ]));

        $guideQuest = GuideQuest::where('name', 'Ignore Old Extra Rows Import Quest')->first();

        $this->assertCount(2, $guideQuest->required_batch_crafted_items);
        $this->assertSame($dagger->id, $guideQuest->required_batch_crafted_items[0]['item_id']);
        $this->assertSame($helmet->id, $guideQuest->required_batch_crafted_items[1]['item_id']);
    }
}
