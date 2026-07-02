<?php

namespace Tests\Feature\Game\GuideQuest\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Game\Character\CharacterInventory\Values\AlchemyItemType;
use App\Game\GuideQuests\Services\GuideQuestService;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGuideQuest;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class GuideQuestsControllerTest extends TestCase
{
    use CreateGuideQuest, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CharacterFactory $character = null;

    private ?GuideQuestService $guideQuestService = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->guideQuestService = resolve(GuideQuestService::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->guideQuestService = null;
    }

    public function testShouldSeeCompletedGuideQuest()
    {
        $quest = $this->createGuideQuest([
            'required_level' => 1
        ]);

        $character = $this->character->updateUser(['guide_enabled' => true])
            ->getCharacter();

        $this->guideQuestService->handInQuest($character, $quest);

        $this->actingAs($character->user)
            ->visit('/game/completed-guide-quests/' . $character->user->id)
            ->see($quest->name);
    }

    public function testShouldBeableToSeeSingleGuidequest()
    {
        $quest = $this->createGuideQuest([
            'required_level' => 1
        ]);

        $character = $this->character->updateUser(['guide_enabled' => true])
            ->getCharacter();

        $this->guideQuestService->handInQuest($character, $quest);

        $this->actingAs($character->user)
            ->visit('/game/completed-guide-quest/' . $character->id . '/' . $quest->id)
            ->see($quest->name);
    }

    public function testCompletedGuideQuestShowPageDisplaysBatchCraftingAndCraftedInventoryRequirementsCleanly(): void
    {
        $plainMace = $this->createItem(['name' => 'Diamond Mace', 'type' => 'mace', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $prefix = $this->createItemAffix(['type' => 'prefix']);
        $suffix = $this->createItemAffix(['type' => 'suffix']);
        $chest = $this->createItem(['name' => 'Paladin\'s Oath Chest', 'type' => 'body', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $this->createItem(['name' => 'Paladin\'s Oath Chest', 'type' => 'body', 'parent_id' => $chest->id, 'item_prefix_id' => $prefix->id, 'item_suffix_id' => $suffix->id]);
        $quest = $this->createGuideQuest([
            'required_batch_crafting_type' => 'craft_and_enchant',
            'required_batch_crafting_hours' => 1,
            'required_batch_crafted_items' => [
                ['source' => 'inventory', 'item_id' => $plainMace->id, 'amount' => 20, 'must_be_enchanted' => false],
                ['source' => 'inventory', 'item_id' => $chest->id, 'amount' => 15, 'must_be_enchanted' => true],
            ],
        ]);
        $character = $this->character->updateUser(['guide_enabled' => true])->getCharacter();

        $this->actingAs($character->user)
            ->visit('/game/completed-guide-quest/' . $character->id . '/' . $quest->id)
            ->see('Required Batch Crafting')
            ->see('Run Craft and Enchant For Experience for at least 1 hour.')
            ->see('Required Item')
            ->see('Have 15x Paladin\'s Oath Chest of type Body in your inventory with both a prefix and a suffix.')
            ->see('Have 20x Diamond Mace of type Mace in your inventory.')
            ->see('Item Consumption')
            ->see('These items are consumed when the guide quest is handed in.')
            ->dontSee('Required Crafted or Alchemy Items')
            ->dontSee('required_batch_crafted_items')
            ->dontSee('item_id')
            ->dontSee('Requireed')
            ->dontSee('Required Strengh');
    }

    public function testCompletedGuideQuestShowPageDisplaysAlchemyBagItemRequirementsCleanly(): void
    {
        $potion = $this->createItem([
            'name' => 'Mixtures and Concoctions',
            'type' => 'alchemy',
            'alchemy_type' => AlchemyItemType::INCREASE_ALCHEMY_SKILL->value,
        ]);
        $quest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['source' => 'alchemy_bag', 'item_id' => $potion->id, 'amount' => 25, 'must_be_enchanted' => false],
            ],
        ]);
        $character = $this->character->updateUser(['guide_enabled' => true])->getCharacter();

        $this->actingAs($character->user)
            ->visit('/game/completed-guide-quest/' . $character->id . '/' . $quest->id)
            ->see('Required Item')
            ->see('Have 25x Mixtures and Concoctions of type Increases Alchemy Skill in your alchemy bag.')
            ->see('Item Consumption')
            ->see('These items are consumed when the guide quest is handed in.')
            ->dontSee('Required Crafted or Alchemy Items')
            ->dontSee('required_batch_crafted_items')
            ->dontSee('item_id');
    }
}
