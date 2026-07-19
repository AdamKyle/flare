<?php

namespace Tests\Unit\Game\GuideQuests\Services;

use App\Flare\Models\CharacterBattleRewardRequest;
use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\QuestsCompleted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Item;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\AutomationType;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\MaxCurrenciesValue;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Character\CharacterInventory\Values\AlchemyItemType;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\ScheduledEventStatus;
use App\Game\GuideQuests\Services\GuideQuestService;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestPriority;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Traits\CreateGuideQuest;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateMonster;

class GuideQuestServiceTest extends TestCase
{
    use CreateBatchCrafting, CreateGuideQuest, CreateItem, CreateItemAffix, CreateMonster, CreateEvent, CreateGlobalEventGoal, RefreshDatabase;

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
        Queue::fake();

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

        $request = CharacterBattleRewardRequest::firstOrFail();
        $this->assertSame(BattleRewardRequestPriority::FIRST, $request->priority);
        $this->assertSame(BattleRewardRequestSourceType::GUIDE_QUEST, $request->source_type);
        $this->assertSame(
            'guide_quest:' . $character->id . ':' . $guideQuestToHandIn->id,
            $request->source_id,
        );

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

    public function testCanHandInQuestWhenOnlyRequirementIsSatisfiedBatchCraftingExperienceHours(): void
    {
        $quest = $this->createGuideQuest([
            'required_batch_crafting_type' => 'craft',
            'required_batch_crafting_hours' => 2,
        ]);

        $character = $this->character->updateUser(['guide_enabled' => true])
            ->getCharacter();

        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => 'craft',
            'started_at' => now()->subHours(2),
            'completed_at' => null,
            'cancelled_at' => null,
            'progress' => [
                'craft_mode' => 'experience',
            ],
        ]);

        $canHandIn = $this->guideQuestService->canHandInQuest($character, $quest);

        $this->assertTrue($canHandIn);
    }

    public function testCanHandInQuestWhenOnlyRequirementIsSatisfiedEventCraftAmount(): void
    {
        $quest = $this->createGuideQuest([
            'required_event_goal_crafting_participation' => 10,
            'only_during_event' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        $character = $this->character->updateUser(['guide_enabled' => true])
            ->getCharacter();

        $schedule = ScheduledEvent::factory()->create([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
        ]);

        $event = $this->createEvent(['type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'scheduled_event_id' => $schedule->id]);

        $eventGoal = $this->createGlobalEventGoal([
            'max_crafts' => 1000,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'unique_type' => RandomAffixDetails::LEGENDARY,
        ]);

        $this->createGlobalEventCrafts([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $character->id,
            'crafts' => 10,
        ]);

        $canHandIn = $this->guideQuestService->canHandInQuest($character->refresh(), $quest);

        $this->assertTrue($canHandIn);
    }

    public function testCanHandInQuestWhenOnlyRequirementIsSatisfiedEventEnchantAmount(): void
    {
        $quest = $this->createGuideQuest([
            'required_event_goal_enchanting_participation' => 10,
            'only_during_event' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        $character = $this->character->updateUser(['guide_enabled' => true])
            ->getCharacter();

        $schedule = ScheduledEvent::factory()->create([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
        ]);

        $event = $this->createEvent(['type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'scheduled_event_id' => $schedule->id]);

        $eventGoal = $this->createGlobalEventGoal([
            'max_enchants' => 1000,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'unique_type' => RandomAffixDetails::LEGENDARY,
        ]);

        $this->createGlobalEventEnchants([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $character->id,
            'enchants' => 10,
        ]);

        $canHandIn = $this->guideQuestService->canHandInQuest($character->refresh(), $quest);

        $this->assertTrue($canHandIn);
    }

    public function testCannotHandInQuestWhenEventCraftAndEnchantRequirementsAreOnlyPartiallySatisfied(): void
    {
        $quest = $this->createGuideQuest([
            'required_event_goal_crafting_participation' => 10,
            'required_event_goal_enchanting_participation' => 10,
            'only_during_event' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        $character = $this->character->updateUser(['guide_enabled' => true])
            ->getCharacter();

        $schedule = ScheduledEvent::factory()->create([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
        ]);

        $event = $this->createEvent(['type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'scheduled_event_id' => $schedule->id]);

        $eventGoal = $this->createGlobalEventGoal([
            'max_crafts' => 1000,
            'max_enchants' => 1000,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'unique_type' => RandomAffixDetails::LEGENDARY,
        ]);

        $this->createGlobalEventCrafts([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $character->id,
            'crafts' => 10,
        ]);

        $canHandIn = $this->guideQuestService->canHandInQuest($character->refresh(), $quest);

        $this->assertFalse($canHandIn);
    }

    public function testHandInConsumesExactRequiredNumberOfPlainMatchingInventorySlots(): void
    {
        $dagger = $this->createItem(['name' => 'Iron Dagger', 'type' => 'dagger', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $quest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['item_id' => $dagger->id, 'amount' => 2, 'must_be_enchanted' => false],
            ],
        ]);
        $character = $this->character->updateUser(['guide_enabled' => true])->getCharacter();

        $slotOne = $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $dagger->id]);
        $slotTwo = $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $dagger->id]);

        $handedIn = $this->guideQuestService->handInQuest($character->refresh(), $quest);

        $this->assertTrue($handedIn);
        $this->assertNull(InventorySlot::find($slotOne->id));
        $this->assertNull(InventorySlot::find($slotTwo->id));
    }

    public function testHandInConsumesExactRequiredNumberOfEnchantedMatchingInventorySlots(): void
    {
        $prefix = $this->createItemAffix(['type' => 'prefix']);
        $suffix = $this->createItemAffix(['type' => 'suffix']);
        $helmet = $this->createItem(['name' => 'Iron Helmet', 'type' => 'helmet', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $enchantedHelmet = $this->createItem(['name' => 'Iron Helmet', 'type' => 'helmet', 'parent_id' => $helmet->id, 'item_prefix_id' => $prefix->id, 'item_suffix_id' => $suffix->id]);
        $quest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['item_id' => $helmet->id, 'amount' => 2, 'must_be_enchanted' => true],
            ],
        ]);
        $character = $this->character->updateUser(['guide_enabled' => true])->getCharacter();

        $slotOne = $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $enchantedHelmet->id]);
        $slotTwo = $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $enchantedHelmet->id]);

        $handedIn = $this->guideQuestService->handInQuest($character->refresh(), $quest);

        $this->assertTrue($handedIn);
        $this->assertNull(InventorySlot::find($slotOne->id));
        $this->assertNull(InventorySlot::find($slotTwo->id));
    }

    public function testHandInDoesNotConsumeItemsWithOnlyPrefixForEnchantedRequirement(): void
    {
        $prefix = $this->createItemAffix(['type' => 'prefix']);
        $helmet = $this->createItem(['name' => 'Iron Helmet', 'type' => 'helmet', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $prefixedHelmet = $this->createItem(['name' => 'Iron Helmet', 'type' => 'helmet', 'parent_id' => $helmet->id, 'item_prefix_id' => $prefix->id, 'item_suffix_id' => null]);
        $quest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['item_id' => $helmet->id, 'amount' => 1, 'must_be_enchanted' => true],
            ],
        ]);
        $character = $this->character->updateUser(['guide_enabled' => true])->getCharacter();

        $slot = $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $prefixedHelmet->id]);

        $handedIn = $this->guideQuestService->handInQuest($character->refresh(), $quest);

        $this->assertFalse($handedIn);
        $this->assertNotNull(InventorySlot::find($slot->id));
    }

    public function testHandInDoesNotConsumeItemsWithOnlySuffixForEnchantedRequirement(): void
    {
        $suffix = $this->createItemAffix(['type' => 'suffix']);
        $helmet = $this->createItem(['name' => 'Iron Helmet', 'type' => 'helmet', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $suffixedHelmet = $this->createItem(['name' => 'Iron Helmet', 'type' => 'helmet', 'parent_id' => $helmet->id, 'item_prefix_id' => null, 'item_suffix_id' => $suffix->id]);
        $quest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['item_id' => $helmet->id, 'amount' => 1, 'must_be_enchanted' => true],
            ],
        ]);
        $character = $this->character->updateUser(['guide_enabled' => true])->getCharacter();

        $slot = $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $suffixedHelmet->id]);

        $handedIn = $this->guideQuestService->handInQuest($character->refresh(), $quest);

        $this->assertFalse($handedIn);
        $this->assertNotNull(InventorySlot::find($slot->id));
    }

    public function testHandInDoesNotConsumeEnchantedItemsForPlainRequirement(): void
    {
        $prefix = $this->createItemAffix(['type' => 'prefix']);
        $suffix = $this->createItemAffix(['type' => 'suffix']);
        $dagger = $this->createItem(['name' => 'Iron Dagger', 'type' => 'dagger', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $enchantedDagger = $this->createItem(['name' => 'Iron Dagger', 'type' => 'dagger', 'parent_id' => $dagger->id, 'item_prefix_id' => $prefix->id, 'item_suffix_id' => $suffix->id]);
        $quest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['item_id' => $dagger->id, 'amount' => 1, 'must_be_enchanted' => false],
            ],
        ]);
        $character = $this->character->updateUser(['guide_enabled' => true])->getCharacter();

        $slot = $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $enchantedDagger->id]);

        $handedIn = $this->guideQuestService->handInQuest($character->refresh(), $quest);

        $this->assertFalse($handedIn);
        $this->assertNotNull(InventorySlot::find($slot->id));
    }

    public function testHandInLeavesExtraMatchingInventorySlotsAlone(): void
    {
        $dagger = $this->createItem(['name' => 'Iron Dagger', 'type' => 'dagger', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $quest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['item_id' => $dagger->id, 'amount' => 2, 'must_be_enchanted' => false],
            ],
        ]);
        $character = $this->character->updateUser(['guide_enabled' => true])->getCharacter();

        $slotOne = $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $dagger->id]);
        $slotTwo = $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $dagger->id]);
        $slotThree = $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $dagger->id]);

        $handedIn = $this->guideQuestService->handInQuest($character->refresh(), $quest);

        $this->assertTrue($handedIn);
        $this->assertNull(InventorySlot::find($slotOne->id));
        $this->assertNull(InventorySlot::find($slotTwo->id));
        $this->assertNotNull(InventorySlot::find($slotThree->id));
    }

    public function testHandInReturnsFalseAndConsumesNothingWhenOneConfiguredRowIsShort(): void
    {
        $dagger = $this->createItem(['name' => 'Iron Dagger', 'type' => 'dagger', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $helmet = $this->createItem(['name' => 'Iron Helmet', 'type' => 'helmet', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $quest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['item_id' => $dagger->id, 'amount' => 1, 'must_be_enchanted' => false],
                ['item_id' => $helmet->id, 'amount' => 1, 'must_be_enchanted' => false],
            ],
        ]);
        $character = $this->character->updateUser(['guide_enabled' => true])->getCharacter();

        $slot = $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $dagger->id]);

        $handedIn = $this->guideQuestService->handInQuest($character->refresh(), $quest);

        $this->assertFalse($handedIn);
        $this->assertNotNull(InventorySlot::find($slot->id));
        $this->assertNull(QuestsCompleted::where('character_id', $character->id)->where('guide_quest_id', $quest->id)->first());
    }

    public function testHandInReturnsFalseWhileActiveBatchCraftingExistsForTheCharacter(): void
    {
        $dagger = $this->createItem(['name' => 'Iron Dagger', 'type' => 'dagger', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $quest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['item_id' => $dagger->id, 'amount' => 1, 'must_be_enchanted' => false],
            ],
        ]);
        $character = $this->character->updateUser(['guide_enabled' => true])->getCharacter();
        $slot = $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $dagger->id]);

        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => 'craft',
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $handedIn = $this->guideQuestService->handInQuest($character->refresh(), $quest);

        $this->assertFalse($handedIn);
        $this->assertNotNull(InventorySlot::find($slot->id));
    }

    public function testCanHandInQuestReturnsTrueWhenItemRequirementsAreSatisfiedAndNoActiveBatchCraftingExists(): void
    {
        $dagger = $this->createItem(['name' => 'Iron Dagger', 'type' => 'dagger', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $quest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['item_id' => $dagger->id, 'amount' => 1, 'must_be_enchanted' => false],
            ],
        ]);
        $character = $this->character->updateUser(['guide_enabled' => true])->getCharacter();

        $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $dagger->id]);

        $canHandIn = $this->guideQuestService->canHandInQuest($character->refresh(), $quest);

        $this->assertTrue($canHandIn);
    }

    public function testHandInDecrementsAlchemyBagSlotAmount(): void
    {
        $potion = $this->createItem(['name' => 'Lesser Stat Potion', 'type' => 'alchemy', 'alchemy_type' => AlchemyItemType::INCREASE_STATS->value]);
        $quest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['source' => 'alchemy_bag', 'item_id' => $potion->id, 'amount' => 3, 'must_be_enchanted' => false],
            ],
        ]);
        $character = $this->character->updateUser(['guide_enabled' => true])->getCharacter();
        $alchemyBagSlot = $character->alchemyBag->slots()->create(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $potion->id, 'amount' => 5]);

        $handedIn = $this->guideQuestService->handInQuest($character->refresh(), $quest);

        $this->assertTrue($handedIn);
        $this->assertSame(2, AlchemyBagSlot::find($alchemyBagSlot->id)->amount);
    }

    public function testHandInDeletesAlchemyBagSlotWhenExactAmountIsConsumed(): void
    {
        $potion = $this->createItem(['name' => 'Lesser Stat Potion', 'type' => 'alchemy', 'alchemy_type' => AlchemyItemType::INCREASE_STATS->value]);
        $quest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['source' => 'alchemy_bag', 'item_id' => $potion->id, 'amount' => 5, 'must_be_enchanted' => false],
            ],
        ]);
        $character = $this->character->updateUser(['guide_enabled' => true])->getCharacter();
        $alchemyBagSlot = $character->alchemyBag->slots()->create(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $potion->id, 'amount' => 5]);

        $handedIn = $this->guideQuestService->handInQuest($character->refresh(), $quest);

        $this->assertTrue($handedIn);
        $this->assertNull(AlchemyBagSlot::find($alchemyBagSlot->id));
    }

    public function testHandInLeavesExtraAlchemyAmountAlone(): void
    {
        $potion = $this->createItem(['name' => 'Lesser Stat Potion', 'type' => 'alchemy', 'alchemy_type' => AlchemyItemType::INCREASE_STATS->value]);
        $quest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['source' => 'alchemy_bag', 'item_id' => $potion->id, 'amount' => 2, 'must_be_enchanted' => false],
            ],
        ]);
        $character = $this->character->updateUser(['guide_enabled' => true])->getCharacter();
        $alchemyBagSlot = $character->alchemyBag->slots()->create(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $potion->id, 'amount' => 7]);

        $handedIn = $this->guideQuestService->handInQuest($character->refresh(), $quest);

        $this->assertTrue($handedIn);
        $this->assertSame(5, AlchemyBagSlot::find($alchemyBagSlot->id)->amount);
    }

    public function testHandInConsumesBothInventoryAndAlchemyRowsInTheSameQuest(): void
    {
        $dagger = $this->createItem(['name' => 'Iron Dagger', 'type' => 'dagger', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $potion = $this->createItem(['name' => 'Lesser Stat Potion', 'type' => 'alchemy', 'alchemy_type' => AlchemyItemType::INCREASE_STATS->value]);
        $quest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['source' => 'inventory', 'item_id' => $dagger->id, 'amount' => 1, 'must_be_enchanted' => false],
                ['source' => 'alchemy_bag', 'item_id' => $potion->id, 'amount' => 3, 'must_be_enchanted' => false],
            ],
        ]);
        $character = $this->character->updateUser(['guide_enabled' => true])->getCharacter();
        $slot = $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $dagger->id]);
        $alchemyBagSlot = $character->alchemyBag->slots()->create(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $potion->id, 'amount' => 4]);

        $handedIn = $this->guideQuestService->handInQuest($character->refresh(), $quest);

        $this->assertTrue($handedIn);
        $this->assertNull(InventorySlot::find($slot->id));
        $this->assertSame(1, AlchemyBagSlot::find($alchemyBagSlot->id)->amount);
    }

    public function testHandInReturnsFalseAndConsumesNothingWhenAlchemyAmountIsShort(): void
    {
        $dagger = $this->createItem(['name' => 'Iron Dagger', 'type' => 'dagger', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $potion = $this->createItem(['name' => 'Lesser Stat Potion', 'type' => 'alchemy', 'alchemy_type' => AlchemyItemType::INCREASE_STATS->value]);
        $quest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['source' => 'inventory', 'item_id' => $dagger->id, 'amount' => 1, 'must_be_enchanted' => false],
                ['source' => 'alchemy_bag', 'item_id' => $potion->id, 'amount' => 3, 'must_be_enchanted' => false],
            ],
        ]);
        $character = $this->character->updateUser(['guide_enabled' => true])->getCharacter();
        $slot = $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $dagger->id]);
        $alchemyBagSlot = $character->alchemyBag->slots()->create(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $potion->id, 'amount' => 2]);

        $handedIn = $this->guideQuestService->handInQuest($character->refresh(), $quest);

        $this->assertFalse($handedIn);
        $this->assertNotNull(InventorySlot::find($slot->id));
        $this->assertSame(2, AlchemyBagSlot::find($alchemyBagSlot->id)->amount);
    }

    public function testFetchNextRegularGuideQuestReturnsFirstIncompleteRoot(): void
    {
        $character = $this->character->updateUser(['guide_enabled' => true])->getCharacter();

        $firstRoot = $this->createGuideQuest(['name' => 'First Root']);
        $this->createGuideQuest(['name' => 'Second Root']);

        $questDetails = $this->guideQuestService->fetchQuestForCharacter($character);

        $this->assertCount(1, $questDetails['quests']);
        $this->assertSame($firstRoot->id, $questDetails['quests'][0]->id);
    }

    public function testFetchNextRegularGuideQuestReturnsIncompleteParentEvenWhenChildCompletedOutOfOrder(): void
    {
        $character = $this->character->updateUser(['guide_enabled' => true])->getCharacter();

        $parent = $this->createGuideQuest(['name' => 'Parent Quest']);
        $child = $this->createGuideQuest(['name' => 'Child Quest', 'parent_id' => $parent->id]);

        QuestsCompleted::create([
            'character_id' => $character->id,
            'guide_quest_id' => $child->id,
        ]);

        $questDetails = $this->guideQuestService->fetchQuestForCharacter($character);

        $this->assertCount(1, $questDetails['quests']);
        $this->assertSame($parent->id, $questDetails['quests'][0]->id);
    }

    public function testFetchNextRegularGuideQuestReturnsFirstIncompleteChildAfterRootCompleted(): void
    {
        $character = $this->character->updateUser(['guide_enabled' => true])->getCharacter();

        $root = $this->createGuideQuest(['name' => 'Root Quest']);
        $child = $this->createGuideQuest(['name' => 'Child Quest', 'parent_id' => $root->id]);

        QuestsCompleted::create([
            'character_id' => $character->id,
            'guide_quest_id' => $root->id,
        ]);

        $questDetails = $this->guideQuestService->fetchQuestForCharacter($character);

        $this->assertCount(1, $questDetails['quests']);
        $this->assertSame($child->id, $questDetails['quests'][0]->id);
    }

    public function testFetchNextRegularGuideQuestReturnsNextIncompleteGrandchildAfterParentAndChildCompleted(): void
    {
        $character = $this->character->updateUser(['guide_enabled' => true])->getCharacter();

        $root = $this->createGuideQuest(['name' => 'Root Quest']);
        $child = $this->createGuideQuest(['name' => 'Child Quest', 'parent_id' => $root->id]);
        $grandchild = $this->createGuideQuest(['name' => 'Grandchild Quest', 'parent_id' => $child->id]);

        QuestsCompleted::create([
            'character_id' => $character->id,
            'guide_quest_id' => $root->id,
        ]);
        QuestsCompleted::create([
            'character_id' => $character->id,
            'guide_quest_id' => $child->id,
        ]);

        $questDetails = $this->guideQuestService->fetchQuestForCharacter($character);

        $this->assertCount(1, $questDetails['quests']);
        $this->assertSame($grandchild->id, $questDetails['quests'][0]->id);
    }

    public function testFetchNextRegularGuideQuestReturnsNextRootAfterCompleteDescendantTree(): void
    {
        $character = $this->character->updateUser(['guide_enabled' => true])->getCharacter();

        $firstRoot = $this->createGuideQuest(['name' => 'First Root']);
        $firstRootChild = $this->createGuideQuest(['name' => 'First Root Child', 'parent_id' => $firstRoot->id]);
        $secondRoot = $this->createGuideQuest(['name' => 'Second Root']);

        QuestsCompleted::create([
            'character_id' => $character->id,
            'guide_quest_id' => $firstRoot->id,
        ]);
        QuestsCompleted::create([
            'character_id' => $character->id,
            'guide_quest_id' => $firstRootChild->id,
        ]);

        $questDetails = $this->guideQuestService->fetchQuestForCharacter($character);

        $this->assertCount(1, $questDetails['quests']);
        $this->assertSame($secondRoot->id, $questDetails['quests'][0]->id);
    }

    public function testFetchNextRegularGuideQuestOrdersMultipleChildrenById(): void
    {
        $character = $this->character->updateUser(['guide_enabled' => true])->getCharacter();

        $root = $this->createGuideQuest(['name' => 'Root Quest']);

        QuestsCompleted::create([
            'character_id' => $character->id,
            'guide_quest_id' => $root->id,
        ]);

        $lowerIdChild = $this->createGuideQuest(['name' => 'Z Child', 'parent_id' => $root->id]);
        $this->createGuideQuest(['name' => 'A Child', 'parent_id' => $root->id]);

        $questDetails = $this->guideQuestService->fetchQuestForCharacter($character);

        $this->assertCount(1, $questDetails['quests']);
        $this->assertSame($lowerIdChild->id, $questDetails['quests'][0]->id);
    }

    public function testFetchNextRegularGuideQuestExactSequenceWhenAutomatingTheSmithingProcessAlreadyCompleted(): void
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

        $questDetails = $this->guideQuestService->fetchQuestForCharacter($character);
        $this->assertSame($blacksmithsLife->id, $questDetails['quests'][0]->id);

        QuestsCompleted::create([
            'character_id' => $character->id,
            'guide_quest_id' => $blacksmithsLife->id,
        ]);

        $questDetails = $this->guideQuestService->fetchQuestForCharacter($character);
        $this->assertSame($theEnchantress->id, $questDetails['quests'][0]->id);

        QuestsCompleted::create([
            'character_id' => $character->id,
            'guide_quest_id' => $theEnchantress->id,
        ]);

        $questDetails = $this->guideQuestService->fetchQuestForCharacter($character);
        $this->assertSame($efficiencyIsKey->id, $questDetails['quests'][0]->id);

        QuestsCompleted::create([
            'character_id' => $character->id,
            'guide_quest_id' => $efficiencyIsKey->id,
        ]);

        $questDetails = $this->guideQuestService->fetchQuestForCharacter($character);
        $this->assertSame($enchantingIsKey->id, $questDetails['quests'][0]->id);

        QuestsCompleted::create([
            'character_id' => $character->id,
            'guide_quest_id' => $enchantingIsKey->id,
        ]);

        $questDetails = $this->guideQuestService->fetchQuestForCharacter($character);
        $this->assertSame($allureQuest->id, $questDetails['quests'][0]->id);

        QuestsCompleted::create([
            'character_id' => $character->id,
            'guide_quest_id' => $allureQuest->id,
        ]);

        $questDetails = $this->guideQuestService->fetchQuestForCharacter($character);
        $this->assertEmpty($questDetails['quests']);
    }

    public function testFetchNextRegularGuideQuestDoesNotAffectEventGuideQuests(): void
    {
        $character = $this->character->updateUser(['guide_enabled' => true])->getCharacter();
        $character->update(['level' => 10]);
        $character = $character->refresh();

        $eventQuest = $this->createGuideQuest([
            'name' => 'Unlocks At Level Quest',
            'unlock_at_level' => 10,
        ]);

        $regularRoot = $this->createGuideQuest(['name' => 'Regular Root']);
        $regularChild = $this->createGuideQuest(['name' => 'Regular Child', 'parent_id' => $regularRoot->id]);

        QuestsCompleted::create([
            'character_id' => $character->id,
            'guide_quest_id' => $regularRoot->id,
        ]);

        $questDetails = $this->guideQuestService->fetchQuestForCharacter($character);

        $questIds = collect($questDetails['quests'])->pluck('id')->all();

        $this->assertContains($eventQuest->id, $questIds);
        $this->assertContains($regularChild->id, $questIds);
    }

    public function testFetchNextRegularGuideQuestReturnsNullWhenAllRegularGuideQuestsComplete(): void
    {
        $character = $this->character->updateUser(['guide_enabled' => true])->getCharacter();

        $root = $this->createGuideQuest(['name' => 'Root Quest']);
        $child = $this->createGuideQuest(['name' => 'Child Quest', 'parent_id' => $root->id]);

        QuestsCompleted::create([
            'character_id' => $character->id,
            'guide_quest_id' => $root->id,
        ]);
        QuestsCompleted::create([
            'character_id' => $character->id,
            'guide_quest_id' => $child->id,
        ]);

        $questDetails = $this->guideQuestService->fetchQuestForCharacter($character);

        $this->assertEmpty($questDetails['quests']);
    }
}
