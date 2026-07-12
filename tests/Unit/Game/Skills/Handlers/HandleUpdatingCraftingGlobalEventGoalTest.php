<?php

namespace Tests\Unit\Game\Skills\Handlers;

use App\Flare\Models\GlobalEventCraftingInventory;
use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\WeaponTypes;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Events\Values\ScheduledEventStatus;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Services\ServerMessage;
use App\Game\Skills\Handlers\HandleUpdatingCraftingGlobalEventGoal;
use App\Game\Skills\Values\SkillTypeValue;
use Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateScheduledEvent;

class HandleUpdatingCraftingGlobalEventGoalTest extends TestCase
{
    use CreateEvent, CreateGameMap, CreateGameSkill, CreateGlobalEventGoal, CreateItem, CreateScheduledEvent, RefreshDatabase;

    private ?HandleUpdatingCraftingGlobalEventGoal $handleUpdatingCraftingGlobalEventGoal;

    private ?CharacterFactory $character;

    public function setUp(): void
    {
        parent::setUp();

        $this->handleUpdatingCraftingGlobalEventGoal = resolve(HandleUpdatingCraftingGlobalEventGoal::class);

        $this->character = (new CharacterFactory)->createBaseCharacter()->assignSkill(
            $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value]),
            400
        );
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->handleUpdatingCraftingGlobalEventGoal = null;
        $this->character = null;
    }

    public function testDoNotParticipateInCraftingGlobalEventWhenEventDoesNotExist()
    {
        $item = $this->createItem(['type' => WeaponTypes::WEAPON]);
        $character = $this->character->getCharacter();

        $this->handleUpdatingCraftingGlobalEventGoal->handleUpdatingCraftingGlobalEventGoal($character, $item);

        $character = $character->refresh();

        $this->assertEmpty($character->globalEventCrafts);

        $this->assertEmpty($character->globalEventParticipation);

        $this->assertEmpty(GlobalEventCraftingInventory::all());
    }

    public function testDoNotParticipateInCraftingGlobalEventWhenGlobalEventDoesNotExist()
    {
        $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
        ]);

        $item = $this->createItem(['type' => WeaponTypes::WEAPON]);
        $character = $this->character->getCharacter();

        $this->handleUpdatingCraftingGlobalEventGoal->handleUpdatingCraftingGlobalEventGoal($character, $item);

        $character = $character->refresh();

        $this->assertEmpty($character->globalEventCrafts);

        $this->assertEmpty($character->globalEventParticipation);

        $this->assertEmpty(GlobalEventCraftingInventory::all());
    }

    public function testParticipateInGlobalCraftingEvent()
    {
        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
        ]);

        $goal = $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_crafts' => 100,
            'reward_every' => 10,
            'next_reward_at' => 10,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => false,
            'should_be_mythic' => true,
        ]);

        $map = $this->createGameMap(['only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);

        $item = $this->createItem(['type' => WeaponTypes::WEAPON]);
        $character = $this->character->givePlayerLocation(16, 16, $map)->getCharacter();

        $this->handleUpdatingCraftingGlobalEventGoal->handleUpdatingCraftingGlobalEventGoal($character, $item);

        $character = $character->refresh();

        $this->assertNotNull($character->globalEventCrafts);
        $this->assertNotNull($character->globalEventParticipation);
        $this->assertNotEmpty(GlobalEventCraftingInventory::all());

        $this->assertEquals(1, $character->globalEventCrafts()->where('global_event_goal_id', $goal->id)->first()->crafts);
        $this->assertEquals(1, $character->globalEventParticipation()->where('global_event_goal_id', $goal->id)->first()->current_crafts);

        $this->assertCount(1, GlobalEventCraftingInventorySlot::where('item_id', $item->id)->get());
    }

    public function testParticipateInEventWhenMaxCraftsAreReached()
    {
        Event::fake();

        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
        ]);

        $globalEvent = $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_crafts' => 100,
            'reward_every' => 10,
            'next_reward_at' => 10,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => false,
            'should_be_mythic' => true,
        ]);

        $map = $this->createGameMap(['only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);

        $item = $this->createItem(['type' => WeaponTypes::WEAPON]);
        $character = $this->character->givePlayerLocation(16, 16, $map)->getCharacter();

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $globalEvent->id,
            'character_id' => $character->id,
            'current_crafts' => 100,
        ]);

        $character = $character->refresh();

        $this->handleUpdatingCraftingGlobalEventGoal->handleUpdatingCraftingGlobalEventGoal($character, $item);

        $character = $character->refresh();

        Event::assertDispatched(ServerMessageEvent::class, function ($event) {
            return $event->message === '"Child, We need no more of these." The Red Hawk Soldier states, looking at the item. The event has been finished. The next stage will start soon. Use Craft to craft your own items.';
        });

        $this->assertEmpty($character->globalEventCrafts);
        $this->assertEmpty(GlobalEventCraftingInventory::all());
    }

    public function testParticipateInGlobalCraftingEventWhenWeShouldBeRewarded()
    {
        $this->createItem(['specialty_type' => ItemSpecialtyType::DELUSIONAL_SILVER]);

        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
        ]);

        $eventGoal = $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_crafts' => 100,
            'reward_every' => 10,
            'next_reward_at' => 10,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => false,
            'should_be_mythic' => true,
        ]);

        $map = $this->createGameMap(['only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);

        $character = $this->character->givePlayerLocation(16, 16, $map)->getCharacter();

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $character->id,
            'current_crafts' => 99,
        ]);

        $character->globalEventCrafts()->create([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $character->id,
            'crafts' => 99,
        ]);

        $character = $character->refresh();

        $item = $this->createItem(['type' => WeaponTypes::WEAPON]);

        $this->handleUpdatingCraftingGlobalEventGoal->handleUpdatingCraftingGlobalEventGoal($character, $item);

        $character = $character->refresh();

        $this->assertNotEmpty($character->globalEventCrafts);
        $this->assertNotEmpty($character->globalEventParticipation);
        $this->assertNotEmpty(GlobalEventCraftingInventory::all());

        $this->assertEquals(100, $character->globalEventCrafts()->where('global_event_goal_id', $eventGoal->id)->first()->crafts);
        $this->assertEquals(100, $character->globalEventParticipation()->where('global_event_goal_id', $eventGoal->id)->first()->current_crafts);

        $this->assertCount(1, GlobalEventCraftingInventorySlot::where('item_id', $item->id)->get());

        $foundMythic = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->is_mythic;
        })->first();

        $this->assertNotNull($foundMythic);
    }
}
