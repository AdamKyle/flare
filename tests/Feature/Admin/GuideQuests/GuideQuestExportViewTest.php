<?php

namespace Tests\Feature\Admin\GuideQuests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Game\Character\CharacterInventory\Values\AlchemyItemType;
use Tests\TestCase;
use Tests\Traits\CreateGuideQuest;
use Tests\Traits\CreateItem;

class GuideQuestExportViewTest extends TestCase
{
    use CreateGuideQuest, CreateItem, RefreshDatabase;

    public function testGuideQuestExportViewContainsBatchCraftingRequirementColumnsAndValues(): void
    {
        $guideQuest = $this->createGuideQuest([
            'required_batch_crafting_type' => 'craft_and_enchant',
            'required_batch_crafting_hours' => 3,
        ]);

        $view = view('admin.exports.guide-quests.sheets.guide-quests', [
            'guideQuests' => collect([$guideQuest]),
        ])->render();

        $this->assertStringContainsString('required_batch_crafting_type', $view);
        $this->assertStringContainsString('required_batch_crafting_hours', $view);
        $this->assertStringContainsString('craft_and_enchant', $view);
        $this->assertStringContainsString('3', $view);
    }

    public function testGuideQuestExportViewContainsEventGoalCraftAndEnchantColumnsAndValues(): void
    {
        $guideQuest = $this->createGuideQuest([
            'required_event_goal_crafting_participation' => 12,
            'required_event_goal_enchanting_participation' => 13,
        ]);

        $view = view('admin.exports.guide-quests.sheets.guide-quests', [
            'guideQuests' => collect([$guideQuest]),
        ])->render();

        $this->assertStringContainsString('required_event_goal_crafting_participation', $view);
        $this->assertStringContainsString('required_event_goal_enchanting_participation', $view);
        $this->assertStringContainsString('<td>12</td>', $view);
        $this->assertStringContainsString('<td>13</td>', $view);
    }

    public function testGuideQuestExportViewContainsBatchCraftedItemRequirementColumnsAndValues(): void
    {
        $helmet = $this->createItem(['name' => 'Export Iron Helmet', 'type' => 'helmet', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $potion = $this->createItem(['name' => 'Export Lesser Potion', 'type' => 'alchemy', 'alchemy_type' => AlchemyItemType::INCREASE_STATS->value]);
        $guideQuest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['source' => 'inventory', 'item_id' => $helmet->id, 'amount' => 10, 'must_be_enchanted' => true],
                ['source' => 'alchemy_bag', 'item_id' => $potion->id, 'amount' => 25, 'must_be_enchanted' => false],
            ],
        ]);

        $view = view('admin.exports.guide-quests.sheets.guide-quests', [
            'guideQuests' => collect([$guideQuest]),
        ])->render();

        $this->assertStringContainsString('required_item_1_source', $view);
        $this->assertStringContainsString('required_item_1_name', $view);
        $this->assertStringContainsString('required_item_1_type', $view);
        $this->assertStringContainsString('required_item_1_amount', $view);
        $this->assertStringContainsString('required_item_1_must_be_enchanted', $view);
        $this->assertStringContainsString('required_item_2_source', $view);
        $this->assertStringContainsString('required_item_2_name', $view);
        $this->assertStringContainsString('required_item_2_type', $view);
        $this->assertStringContainsString('required_item_2_amount', $view);
        $this->assertStringContainsString('required_item_2_must_be_enchanted', $view);
        $this->assertStringNotContainsString('required_item_3_source', $view);
        $this->assertStringNotContainsString('required_batch_crafted_item_3_name', $view);
        $this->assertStringNotContainsString('required_batch_crafted_item_4_name', $view);
        $this->assertStringNotContainsString('required_batch_crafted_item_5_name', $view);
        $this->assertStringContainsString('inventory', $view);
        $this->assertStringContainsString('alchemy_bag', $view);
        $this->assertStringContainsString('Export Iron Helmet', $view);
        $this->assertStringContainsString('Helmet', $view);
        $this->assertStringContainsString('10', $view);
        $this->assertStringContainsString('1', $view);
        $this->assertStringContainsString('Export Lesser Potion', $view);
        $this->assertStringContainsString('Increases Stats', $view);
        $this->assertStringContainsString('25', $view);
    }

    public function testGuideQuestExportViewHasValidTableMarkupForNewRequirementFields(): void
    {
        $guideQuest = $this->createGuideQuest([
            'required_batch_crafting_type' => 'alchemy',
            'required_batch_crafting_hours' => 2,
        ]);

        $view = view('admin.exports.guide-quests.sheets.guide-quests', [
            'guideQuests' => collect([$guideQuest]),
        ])->render();

        $this->assertStringContainsString('<th>required_batch_crafting_type</th>', $view);
        $this->assertStringContainsString('<th>required_batch_crafting_hours</th>', $view);
        $this->assertStringContainsString('<th>required_item_1_source</th>', $view);
        $this->assertStringContainsString('<th>required_item_2_must_be_enchanted</th>', $view);
        $this->assertStringContainsString('<td>alchemy</td>', $view);
        $this->assertStringContainsString('<td>2</td>', $view);
        $this->assertStringNotContainsString('<th>parent_id<th>', $view);
        $this->assertStringNotContainsString('<th>unlock_at_level<th>', $view);
        $this->assertStringNotContainsString('<th>only_during_event<th>', $view);
        $this->assertStringNotContainsString('<td>{{', $view);
    }
}
