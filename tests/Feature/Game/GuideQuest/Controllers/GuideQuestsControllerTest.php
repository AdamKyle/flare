<?php

namespace Tests\Feature\Game\GuideQuest\Controllers;

use App\Flare\Models\CharacterBattleRewardRequest;
use App\Flare\Models\QuestsCompleted;
use App\Game\Core\Items\Values\AlchemyItemType;
use App\Game\GuideQuests\Services\GuideQuestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->guideQuestService = resolve(GuideQuestService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->guideQuestService = null;
    }

    public function test_repeated_guide_quest_hand_in_creates_one_completion_and_one_reward_request(): void
    {
        $quest = $this->createGuideQuest(['required_level' => 1]);
        $character = $this->character->updateUser(['guide_enabled' => true])->getCharacter();

        $firstResult = $this->guideQuestService->handInQuest($character, $quest);
        $secondResult = $this->guideQuestService->handInQuest($character->refresh(), $quest->refresh());

        $this->assertTrue($firstResult);
        $this->assertFalse($secondResult);
        $this->assertSame(1, QuestsCompleted::where('character_id', $character->id)->where('guide_quest_id', $quest->id)->count());
        $this->assertSame(1, CharacterBattleRewardRequest::where('character_id', $character->id)->count());
    }

    public function test_should_see_completed_guide_quest()
    {
        $quest = $this->createGuideQuest([
            'required_level' => 1,
        ]);

        $character = $this->character->updateUser(['guide_enabled' => true])
            ->getCharacter();

        $this->guideQuestService->handInQuest($character, $quest);

        $response = $this->actingAs($character->user)->get('/game/completed-guide-quests/'.$character->user->id);

        $response->assertSee($quest->name);
    }

    public function test_should_beable_to_see_single_guidequest()
    {
        $quest = $this->createGuideQuest([
            'required_level' => 1,
        ]);

        $character = $this->character->updateUser(['guide_enabled' => true])
            ->getCharacter();

        $this->guideQuestService->handInQuest($character, $quest);

        $response = $this->actingAs($character->user)->get('/game/completed-guide-quest/'.$character->id.'/'.$quest->id);

        $response->assertSee($quest->name);
    }

    public function test_completed_guide_quest_show_page_displays_batch_crafting_and_crafted_inventory_requirements_cleanly(): void
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

        $response = $this->actingAs($character->user)->get('/game/completed-guide-quest/'.$character->id.'/'.$quest->id);

        $response->assertSee('Required Batch Crafting');
        $response->assertSee('Run Craft and Enchant For Experience for at least 1 hour.');
        $response->assertSee('Required Item');
        $response->assertSee('Have 15x Paladin\'s Oath Chest of type Body in your inventory with both a prefix and a suffix.');
        $response->assertSee('Have 20x Diamond Mace of type Mace in your inventory.');
        $response->assertSee('Item Consumption');
        $response->assertSee('These items are consumed when the guide quest is handed in.');
        $response->assertDontSee('Required Crafted or Alchemy Items');
        $response->assertDontSee('required_batch_crafted_items');
        $response->assertDontSee('item_id');
        $response->assertDontSee('Requireed');
        $response->assertDontSee('Required Strengh');
    }

    public function test_completed_guide_quest_show_page_displays_alchemy_bag_item_requirements_cleanly(): void
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

        $response = $this->actingAs($character->user)->get('/game/completed-guide-quest/'.$character->id.'/'.$quest->id);

        $response->assertSee('Required Item');
        $response->assertSee('Have 25x Mixtures and Concoctions of type Increases Alchemy Skill in your alchemy bag.');
        $response->assertSee('Item Consumption');
        $response->assertSee('These items are consumed when the guide quest is handed in.');
        $response->assertDontSee('Required Crafted or Alchemy Items');
        $response->assertDontSee('required_batch_crafted_items');
        $response->assertDontSee('item_id');
    }

    public function test_api_refresh_returns_exact_regular_guide_quest_progression_sequence(): void
    {
        $character = $this->character->updateUser(['guide_enabled' => true])->getCharacter();

        $blacksmithsLife = $this->createGuideQuest(['name' => 'Blacksmiths Life']);
        $automatingSmithing = $this->createGuideQuest(['name' => 'Automating the smithing process', 'parent_id' => $blacksmithsLife->id]);
        $theEnchantress = $this->createGuideQuest(['name' => 'The Enchantress']);
        $efficiencyIsKey = $this->createGuideQuest(['name' => 'Effeciency is key', 'parent_id' => $theEnchantress->id]);
        $enchantingIsKey = $this->createGuideQuest(['name' => 'Enchanting is key']);
        $allureQuest = $this->createGuideQuest(['name' => 'The alure of The Entranchtress', 'parent_id' => $enchantingIsKey->id]);

        QuestsCompleted::create([
            'character_id' => $character->id,
            'guide_quest_id' => $automatingSmithing->id,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/guide-quest/'.$character->user->id, ['_token' => csrf_token()]);
        $jsonData = json_decode($response->getContent(), true);
        $this->assertSame($blacksmithsLife->id, $jsonData['quests'][0]['id']);
        $this->assertSame('Blacksmiths Life', $jsonData['quests'][0]['name']);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/guide-quest/'.$character->user->id, ['_token' => csrf_token()]);
        $jsonData = json_decode($response->getContent(), true);
        $this->assertSame($blacksmithsLife->id, $jsonData['quests'][0]['id']);
        $this->assertSame('Blacksmiths Life', $jsonData['quests'][0]['name']);

        QuestsCompleted::create([
            'character_id' => $character->id,
            'guide_quest_id' => $blacksmithsLife->id,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/guide-quest/'.$character->user->id, ['_token' => csrf_token()]);
        $jsonData = json_decode($response->getContent(), true);
        $this->assertSame($theEnchantress->id, $jsonData['quests'][0]['id']);
        $this->assertSame('The Enchantress', $jsonData['quests'][0]['name']);

        QuestsCompleted::create([
            'character_id' => $character->id,
            'guide_quest_id' => $theEnchantress->id,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/guide-quest/'.$character->user->id, ['_token' => csrf_token()]);
        $jsonData = json_decode($response->getContent(), true);
        $this->assertSame($efficiencyIsKey->id, $jsonData['quests'][0]['id']);
        $this->assertSame('Effeciency is key', $jsonData['quests'][0]['name']);

        QuestsCompleted::create([
            'character_id' => $character->id,
            'guide_quest_id' => $efficiencyIsKey->id,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/guide-quest/'.$character->user->id, ['_token' => csrf_token()]);
        $jsonData = json_decode($response->getContent(), true);
        $this->assertSame($enchantingIsKey->id, $jsonData['quests'][0]['id']);
        $this->assertSame('Enchanting is key', $jsonData['quests'][0]['name']);

        QuestsCompleted::create([
            'character_id' => $character->id,
            'guide_quest_id' => $enchantingIsKey->id,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/guide-quest/'.$character->user->id, ['_token' => csrf_token()]);
        $jsonData = json_decode($response->getContent(), true);
        $this->assertSame($allureQuest->id, $jsonData['quests'][0]['id']);
        $this->assertSame('The alure of The Entranchtress', $jsonData['quests'][0]['name']);

        QuestsCompleted::create([
            'character_id' => $character->id,
            'guide_quest_id' => $allureQuest->id,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/guide-quest/'.$character->user->id, ['_token' => csrf_token()]);
        $jsonData = json_decode($response->getContent(), true);
        $this->assertEmpty($jsonData['quests']);
    }
}
