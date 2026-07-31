<?php

namespace Tests\Feature\Admin\GuideQuests;

use App\Game\Character\CharacterInventory\Values\AlchemyItemType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGuideQuest;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class GuideQuestControllerTest extends TestCase
{
    use CreateGuideQuest, CreateItem, CreateRole, CreateUser, RefreshDatabase;

    public function test_guide_quest_create_page_shows_event_goal_participation_requirement_inputs(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createItem(['name' => 'Controller Filler Quest Item', 'type' => 'quest']);
        $this->createItem(['name' => 'Controller Filler Trinket', 'type' => 'trinket']);

        $this->actingAs($admin)
            ->visitRoute('admin.guide-quests.create')
            ->see('Required Event Goal Kills:')
            ->see('Required Event Goal Crafts:')
            ->see('Required Event Goal Enchants:');
    }

    public function test_guide_quest_edit_page_shows_event_goal_participation_requirement_inputs(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $guideQuest = $this->createGuideQuest();
        $this->createItem(['name' => 'Controller Filler Quest Item', 'type' => 'quest']);
        $this->createItem(['name' => 'Controller Filler Trinket', 'type' => 'trinket']);

        $this->actingAs($admin)
            ->visitRoute('admin.guide-quests.edit', ['guideQuest' => $guideQuest->id])
            ->see('Required Event Goal Kills:')
            ->see('Required Event Goal Crafts:')
            ->see('Required Event Goal Enchants:');
    }

    public function test_guide_quest_show_page_displays_event_goal_craft_and_enchant_requirements(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $guideQuest = $this->createGuideQuest([
            'required_event_goal_crafting_participation' => 12,
            'required_event_goal_enchanting_participation' => 13,
        ]);

        $this->actingAs($admin)
            ->visitRoute('admin.guide-quests.show', ['guideQuest' => $guideQuest->id])
            ->see('Event Goal Crafts')
            ->see('12')
            ->see('Event Goal Enchants')
            ->see('13');
    }

    public function test_crafted_item_option_values_use_real_item_ids(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createItem(['name' => 'Controller Filler Quest Item', 'type' => 'quest']);
        $this->createItem(['name' => 'Controller Filler Trinket', 'type' => 'trinket']);
        $dagger = $this->createItem([
            'name' => 'Controller Iron Dagger',
            'type' => 'dagger',
            'can_craft' => true,
            'item_prefix_id' => null,
            'item_suffix_id' => null,
        ]);

        $this->actingAs($admin)
            ->visitRoute('admin.guide-quests.create')
            ->see('value="'.$dagger->id.'"')
            ->see('Crafted &gt; Weapon &gt; Daggers &gt; Controller Iron Dagger')
            ->dontSee('value="0">Crafted &gt; Weapon &gt; Daggers &gt; Controller Iron Dagger')
            ->dontSee('value="1">Crafted &gt; Weapon &gt; Daggers &gt; Controller Iron Dagger');
    }

    public function test_alchemy_item_option_values_use_real_item_ids(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createItem(['name' => 'Controller Filler Quest Item', 'type' => 'quest']);
        $this->createItem(['name' => 'Controller Filler Trinket', 'type' => 'trinket']);
        $potion = $this->createItem([
            'name' => 'Controller Lesser Stat Potion',
            'type' => 'alchemy',
            'alchemy_type' => AlchemyItemType::INCREASE_STATS->value,
        ]);

        $this->actingAs($admin)
            ->visitRoute('admin.guide-quests.create')
            ->see('value="'.$potion->id.'"')
            ->see('Alchemy &gt; Increases Stats &gt; Controller Lesser Stat Potion')
            ->dontSee('value="0">Alchemy &gt; Increases Stats &gt; Controller Lesser Stat Potion')
            ->dontSee('value="1">Alchemy &gt; Increases Stats &gt; Controller Lesser Stat Potion');
    }
}
