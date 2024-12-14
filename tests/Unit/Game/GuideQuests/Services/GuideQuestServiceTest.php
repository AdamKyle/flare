<?php

namespace Tests\Unit\Game\GuideQuests\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Item;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\AutomationType;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Events\Values\EventType;
use App\Game\GuideQuests\Services\GuideQuestService;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGuideQuest;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMonster;

class GuideQuestServiceTest extends TestCase
{
    use CreateGuideQuest, CreateItem, CreateMonster, CreateEvent, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?GuideQuestService $guideQuestService;

    private ?Item $item;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        $this->guideQuestService = resolve(GuideQuestService::class);

        $this->item = $this->createItem(['type' => 'quest']);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->item = null;
        $this->guideQuestService = null;
    }

    public function testHasNoGuideQues()
    {
        $character = $this->character->updateUser(['guide_enabled' => true])
            ->getCharacter();

        $questDetails = $this->guideQuestService->fetchQuestForCharacter($character);

        $this->assertEmpty($questDetails['quests']);
        $this->assertEmpty($questDetails['completed_requirements']);
        $this->assertEmpty($questDetails['can_hand_in']);
    }

    public function testHasQuestForWinterEventWithUnlocksAtLevel()
    {
        $this->createGuideQuest([
            'required_quest_item_id' => $this->item->id,
            'unlock_at_level' => 10,
            'only_during_event' => EventType::WINTER_EVENT,
        ]);

        $this->createEvent([
            'type' => EventType::WINTER_EVENT
        ]);

        $character = $this->character->updateUser(['guide_enabled' => true])
            ->getCharacter();

        $character->update(['level' => 10]);

        $character = $character->refresh();

        $questDetails = $this->guideQuestService->fetchQuestForCharacter($character);

        $this->assertCount(1, $questDetails['quests']);
    }


    public function testHasQuestForWinterEventWithoutUnlocksAtLevel()
    {
        $this->createGuideQuest([
            'required_quest_item_id' => $this->item->id,
            'only_during_event' => EventType::WINTER_EVENT,
        ]);

        $this->createEvent([
            'type' => EventType::WINTER_EVENT
        ]);

        $character = $this->character->updateUser(['guide_enabled' => true])
            ->getCharacter();

        $character->update(['level' => 10]);

        $character = $character->refresh();

        $questDetails = $this->guideQuestService->fetchQuestForCharacter($character);

        $this->assertCount(1, $questDetails['quests']);
    }

    public function testHasQuestForDelusionalMemoriesEventWithUnlocksAtLevel()
    {
        $this->createGuideQuest([
            'required_quest_item_id' => $this->item->id,
            'unlock_at_level' => 10,
            'only_during_event' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT
        ]);

        $character = $this->character->updateUser(['guide_enabled' => true])
            ->getCharacter();

        $character->update(['level' => 10]);

        $character = $character->refresh();

        $questDetails = $this->guideQuestService->fetchQuestForCharacter($character);

        $this->assertCount(1, $questDetails['quests']);
    }

    public function testHasQuestForDelusionalMemoriesEventWitouthUnlocksAtLevel()
    {
        $this->createGuideQuest([
            'required_quest_item_id' => $this->item->id,
            'only_during_event' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT
        ]);

        $character = $this->character->updateUser(['guide_enabled' => true])
            ->getCharacter();

        $character->update(['level' => 10]);

        $character = $character->refresh();

        $questDetails = $this->guideQuestService->fetchQuestForCharacter($character);

        $this->assertCount(1, $questDetails['quests']);
    }

    public function testHasQuestThatUnlocksAtSpecificLevel()
    {
        $this->createGuideQuest([
            'required_quest_item_id' => $this->item->id,
            'unlock_at_level' => 10,
        ]);

        $character = $this->character->updateUser(['guide_enabled' => true])
            ->getCharacter();

        $character->update(['level' => 10]);

        $character = $character->refresh();

        $questDetails = $this->guideQuestService->fetchQuestForCharacter($character);

        $this->assertCount(1, $questDetails['quests']);
    }

    public function testCharacterCannotHandInGuideQuest()
    {

        $quest = $this->createGuideQuest([
            'required_quest_item_id' => $this->item->id,
        ]);

        $character = $this->character->updateUser(['guide_enabled' => true])
            ->getCharacter();

        $handedIn = $this->guideQuestService->handInQuest($character, $quest);

        $this->assertFalse($handedIn);
    }

    public function testCharacterHasARequirementFromTheGuideQuest()
    {
        $this->createGuideQuest([
            'required_quest_item_id' => $this->item->id,
        ]);

        $character = $this->character->updateUser(['guide_enabled' => true])
            ->inventoryManagement()
            ->giveItem($this->item)
            ->getCharacter();

        $questDetails = $this->guideQuestService->fetchQuestForCharacter($character);

        $this->assertNotEmpty($questDetails['completed_requirements']);
    }

    public function testHandInGuideQuestAndAlreadyHaveOneOfTheRequirements()
    {

        $guideQuestToHandIn = $this->createGuideQuest([
            'required_level' => 1,
        ]);

        $this->createGuideQuest([
            'required_quest_item_id' => $this->item->id,
        ]);

        $character = $this->character->updateUser(['guide_enabled' => true])
            ->inventoryManagement()
            ->giveItem($this->item)
            ->getCharacter();

        $this->guideQuestService->handInQuest($character, $guideQuestToHandIn);

        $questDetails = $this->guideQuestService->fetchQuestForCharacter($character);

        foreach ($questDetails['completed_requirements'] as $completedRequirements) {
            $this->assertContains('required_quest_item_id', $completedRequirements['completed_requirements']);
        }
    }

    public function testHandInGuideQuestAndGetsNextChildQuest()
    {

        $guideQuestToHandIn = $this->createGuideQuest([
            'unlock_at_level' => 1,
            'required_level' => 1,
        ]);

        $this->createGuideQuest([
            'unlock_at_level' => 1,
            'required_level' => 10,
            'parent_id' => $guideQuestToHandIn->id,
        ]);

        $character = $this->character->updateUser(['guide_enabled' => true])
            ->inventoryManagement()
            ->giveItem($this->item)
            ->getCharacter();

        $this->guideQuestService->handInQuest($character, $guideQuestToHandIn);

        $questDetails = $this->guideQuestService->fetchQuestForCharacter($character);

        $this->assertCount(1, $questDetails['quests']);
    }

    public function testHandInUnlockAtLevelGuideQuestAndGetsRegularGuideQuest()
    {

        $guideQuestToHandIn = $this->createGuideQuest([
            'unlock_at_level' => 1,
            'required_level' => 1,
        ]);

        $this->createGuideQuest([
            'unlock_at_level' => 5,
            'required_level' => 10,
        ]);

        $this->createGuideQuest([
            'required_level' => 20,
        ]);

        $character = $this->character->updateUser(['guide_enabled' => true])
            ->inventoryManagement()
            ->giveItem($this->item)
            ->getCharacter();

        $this->guideQuestService->handInQuest($character, $guideQuestToHandIn);

        $questDetails = $this->guideQuestService->fetchQuestForCharacter($character);

        $this->assertCount(1, $questDetails['quests']);
    }

    public function testCharacterCanHandInWithMaxedCurrencies()
    {

        $quest = $this->createGuideQuest([
            'required_quest_item_id' => $this->item->id,
            'gold_reward' => 200,
            'gold_dust_reward' => 200,
            'shards_reward' => 200,
        ]);

        $character = $this->character->updateUser(['guide_enabled' => true])
            ->inventoryManagement()
            ->giveItem($this->item)
            ->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
        ]);

        $handedIn = $this->guideQuestService->handInQuest($character, $quest);

        $this->assertTrue($handedIn);

        $questDetails = $this->guideQuestService->fetchQuestForCharacter($character);

        foreach ($questDetails['completed_requirements'] as $completedRequirements) {
            $this->assertContains('required_quest_item_id', $completedRequirements['completed_requirements']);
        }

        $character = $character->refresh();

        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD, $character->gold);
        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD_DUST, $character->gold_dust);
        $this->assertEquals(MaxCurrenciesValue::MAX_SHARDS, $character->shards);
    }

    public function testCharacterIsNotRewardedWithXPWhenGuidequestProvidesNone()
    {
        $quest = $this->createGuideQuest([
            'required_quest_item_id' => $this->item->id,
            'xp_reward' => 0
        ]);

        $character = $this->character->updateUser(['guide_enabled' => true])
            ->inventoryManagement()
            ->giveItem($this->item)
            ->getCharacter();

        $character->update([
            'xp' => 10
        ]);

        $character = $character->refresh();

        $handedIn = $this->guideQuestService->handInQuest($character, $quest);

        $this->assertTrue($handedIn);

        $questDetails = $this->guideQuestService->fetchQuestForCharacter($character);

        foreach ($questDetails['completed_requirements'] as $completedRequirements) {
            $this->assertContains('required_quest_item_id', $completedRequirements['completed_requirements']);
        }

        $character = $character->refresh();

        $this->assertEquals(10, $character->xp);
    }

    public function testDoNotHandInAQuestThatWasAlreadyCompleted()
    {
        $quest = $this->createGuideQuest([
            'required_quest_item_id' => $this->item->id,
            'gold_reward' => 200,
            'gold_dust_reward' => 200,
            'shards_reward' => 200,
        ]);

        $character = $this->character->updateUser(['guide_enabled' => true])
            ->inventoryManagement()
            ->giveItem($this->item)
            ->getCharacter();

        $handedIn = $this->guideQuestService->handInQuest($character, $quest);

        $this->assertTrue($handedIn);

        $character = $character->refresh();

        $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id' => $this->item->id,
        ]);

        $character = $character->refresh();

        $canHandIn = $this->guideQuestService->canHandInQuest($character, $quest);

        $this->assertFalse($canHandIn);
    }

    public function testCannotHandInWhenAutomationIsRunning()
    {
        $quest = $this->createGuideQuest([
            'required_quest_item_id' => $this->item->id,
            'gold_reward' => 200,
            'gold_dust_reward' => 200,
            'shards_reward' => 200,
        ]);

        $character = $this->character->updateUser(['guide_enabled' => true])
            ->inventoryManagement()
            ->giveItem($this->item)
            ->getCharacter();

        $monster = $this->createMonster();

        $character->currentAutomations()->create([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHours(1),
            'current_level' => $character->level,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $canHandIn = $this->guideQuestService->canHandInQuest($character, $quest);

        $this->assertFalse($canHandIn);
    }
}
