<?php

namespace Tests\Feature\Admin\GuideQuests;

use App\Game\Character\CharacterInventory\Values\AlchemyItemType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class GuideQuestControllerTest extends TestCase
{
    use CreateItem, CreateRole, CreateUser, RefreshDatabase;

    public function testCraftedItemOptionValuesUseRealItemIds(): void
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
            ->see('value="' . $dagger->id . '"')
            ->see('Crafted &gt; Weapon &gt; Daggers &gt; Controller Iron Dagger')
            ->dontSee('value="0">Crafted &gt; Weapon &gt; Daggers &gt; Controller Iron Dagger')
            ->dontSee('value="1">Crafted &gt; Weapon &gt; Daggers &gt; Controller Iron Dagger');
    }

    public function testAlchemyItemOptionValuesUseRealItemIds(): void
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
            ->see('value="' . $potion->id . '"')
            ->see('Alchemy &gt; Increases Stats &gt; Controller Lesser Stat Potion')
            ->dontSee('value="0">Alchemy &gt; Increases Stats &gt; Controller Lesser Stat Potion')
            ->dontSee('value="1">Alchemy &gt; Increases Stats &gt; Controller Lesser Stat Potion');
    }
}
