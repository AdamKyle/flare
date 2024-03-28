<?php

namespace Tests\Console\Events;

use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\GlobalEventCraft;
use App\Flare\Models\GlobalEventCraftingInventory;
use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Flare\Models\GlobalEventEnchant;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\GlobalEventKill;
use App\Flare\Models\GlobalEventParticipation;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Events\Values\GlobalEventForEventTypeValue;
use App\Game\Events\Values\GlobalEventSteps;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Values\ItemSpecialtyType;
use App\Game\Events\Values\EventType;
use Illuminate\Support\Facades\DB;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGlobalEventGoal;

class RestartGlobalEventGoalTest extends TestCase {
    use RefreshDatabase, CreateGlobalEventGoal, CreateEvent;

    private ?Character $character;

    public function setUp(): void {

        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();

    }

    public function tearDown(): void {

        parent::tearDown();

        $this->character = null;
    }

    private function cleanUpPreviousEvents() {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        GlobalEventGoal::truncate();
        GlobalEventKill::truncate();
        GlobalEventCraft::truncate();
        GlobalEventEnchant::truncate();
        GlobalEventParticipation::truncate();
        GlobalEventCraftingInventory::truncate();
        GlobalEventCraftingInventorySlot::truncate();
        Event::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function testRestEventGoal() {

        $this->cleanUpPreviousEvents();

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT
        ]);

        $eventGoal = $this->createGlobalEventGoal([
            'max_kills'                      => 1000,
            'reward_every'                   => 100,
            'next_reward_at'                 => 100,
            'event_type'                     => $event->type,
            'item_specialty_type_reward'     => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique'               => true,
            'unique_type'                    => RandomAffixDetails::LEGENDARY,
            'should_be_mythic'               => false,
        ]);

        $this->createGlobalEventKill([
            'global_event_goal_id'  => $eventGoal->id,
            'character_id'          => $this->character->id,
            'kills'                 => 1000,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $eventGoal->id,
            'character_id'         => $this->character->id,
            'current_kills'        => 1000,
        ]);

        $this->artisan('restart:global-event-goal');

        $eventGoal = $eventGoal->refresh();

        $this->assertEmpty($eventGoal->globalEventParticipation);
        $this->assertEmpty($eventGoal->globalEventKills);
    }

    public function testDoNotRestEventGoal() {

        $this->cleanUpPreviousEvents();

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT
        ]);

        $eventGoal = $this->createGlobalEventGoal([
            'max_kills'                      => 1000,
            'reward_every'                   => 100,
            'next_reward_at'                 => 100,
            'event_type'                     => $event->type,
            'item_specialty_type_reward'     => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique'               => true,
            'unique_type'                    => RandomAffixDetails::LEGENDARY,
            'should_be_mythic'               => false,
        ]);

        $this->createGlobalEventKill([
            'global_event_goal_id'  => $eventGoal->id,
            'character_id'          => $this->character->id,
            'kills'                 => 10,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $eventGoal->id,
            'character_id'         => $this->character->id,
            'current_kills'        => 10,
        ]);

        $this->artisan('restart:global-event-goal');

        $eventGoal = $eventGoal->refresh();

        $this->assertNotEmpty($eventGoal->globalEventParticipation);
        $this->assertNotEmpty($eventGoal->globalEventKills);
    }

    public function testFailToMoveToNextStepWhenEventDoesNotExist() {
        $this->cleanUpPreviousEvents();

        $eventGoal = $this->createGlobalEventGoal([
            'max_enchants'                   => 1000,
            'reward_every'                   => 100,
            'next_reward_at'                 => 100,
            'event_type'                     => EventType::DELUSIONAL_MEMORIES_EVENT,
            'item_specialty_type_reward'     => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique'               => true,
            'unique_type'                    => RandomAffixDetails::LEGENDARY,
            'should_be_mythic'               => false,
        ]);

        $this->createGlobalEventEnchants([
            'global_event_goal_id' => $eventGoal->id,
            'character_id'         => $this->character->id,
            'enchants'             => 1000,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $eventGoal->id,
            'character_id'         => $eventGoal->id,
            'current_enchants'     => 1000,
        ]);

        $inventory = GlobalEventCraftingInventory::create([
            'global_event_id' => $eventGoal->id,
            'character_id'    => $this->character->id,
        ]);

        GlobalEventCraftingInventorySlot::create([
            'global_event_crafting_inventory_id' => $inventory->id,
            'item_id' => 5,
        ]);

        $this->artisan('restart:global-event-goal');

        $newGlobalEventGoal = GlobalEventGoal::where('event_type', EventType::DELUSIONAL_MEMORIES_EVENT)->first();

        $expectedAttributes = GlobalEventForEventTypeValue::returnGlobalEventInfoForSeasonalEvents(EventType::DELUSIONAL_MEMORIES_EVENT);
        $actualAttributes   = $newGlobalEventGoal->toArray();
        $actualAttributes   = array_intersect_key($actualAttributes, $expectedAttributes);

        $this->assertNotEquals($expectedAttributes, $actualAttributes);
        $this->assertNotEmpty(GlobalEventCraftingInventory::all());
        $this->assertNotEmpty(GlobalEventCraftingInventorySlot::all());
    }

    public function testCannotMoveToNextStepWhenNextStepIsNotAValidStep() {

        $this->cleanUpPreviousEvents();

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_goal_steps' => [GlobalEventSteps::BATTLE, GlobalEventSteps::CRAFT, GlobalEventSteps::ENCHANT],
            'current_event_goal_step' => 'apples',
        ]);

        $eventGoal = $this->createGlobalEventGoal([
            'max_enchants'                   => 1000,
            'reward_every'                   => 100,
            'next_reward_at'                 => 100,
            'event_type'                     => $event->type,
            'item_specialty_type_reward'     => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique'               => true,
            'unique_type'                    => RandomAffixDetails::LEGENDARY,
            'should_be_mythic'               => false,
        ]);

        $this->createGlobalEventEnchants([
            'global_event_goal_id' => $eventGoal->id,
            'character_id'         => $this->character->id,
            'enchants'             => 1000,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $eventGoal->id,
            'character_id'         => $eventGoal->id,
            'current_enchants'     => 1000,
        ]);

        $inventory = GlobalEventCraftingInventory::create([
            'global_event_id' => $eventGoal->id,
            'character_id'    => $this->character->id,
        ]);

        GlobalEventCraftingInventorySlot::create([
            'global_event_crafting_inventory_id' => $inventory->id,
            'item_id' => 5,
        ]);

        $this->artisan('restart:global-event-goal');

        $event = $event->refresh();

        $newGlobalEventGoal = GlobalEventGoal::where('event_type', $event->type)->first();

        $this->assertNotEquals(GlobalEventSteps::BATTLE, $event->current_event_goal_step);

        $expectedAttributes = GlobalEventForEventTypeValue::returnGlobalEventInfoForSeasonalEvents($event->type);
        $actualAttributes   = $newGlobalEventGoal->toArray();
        $actualAttributes   = array_intersect_key($actualAttributes, $expectedAttributes);

        $this->assertNotEquals($expectedAttributes, $actualAttributes);
        $this->assertNotEmpty(GlobalEventCraftingInventory::all());
        $this->assertNotEmpty(GlobalEventCraftingInventorySlot::all());
    }

    public function testHandleMovingToTheNextStepFromEnchantingToBattling() {

        $this->cleanUpPreviousEvents();

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_goal_steps' => [GlobalEventSteps::BATTLE, GlobalEventSteps::CRAFT, GlobalEventSteps::ENCHANT],
            'current_event_goal_step' => GlobalEventSteps::ENCHANT,
        ]);

        $eventGoal = $this->createGlobalEventGoal([
            'max_enchants'                   => 1000,
            'reward_every'                   => 100,
            'next_reward_at'                 => 100,
            'event_type'                     => $event->type,
            'item_specialty_type_reward'     => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique'               => true,
            'unique_type'                    => RandomAffixDetails::LEGENDARY,
            'should_be_mythic'               => false,
        ]);

        $this->createGlobalEventEnchants([
            'global_event_goal_id' => $eventGoal->id,
            'character_id'         => $this->character->id,
            'enchants'             => 1000,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $eventGoal->id,
            'character_id'         => $eventGoal->id,
            'current_enchants'     => 1000,
        ]);

        $inventory = GlobalEventCraftingInventory::create([
            'global_event_id' => $eventGoal->id,
            'character_id'    => $this->character->id,
        ]);

        GlobalEventCraftingInventorySlot::create([
            'global_event_crafting_inventory_id' => $inventory->id,
            'item_id' => 5,
        ]);

        $this->artisan('restart:global-event-goal');

        $event = $event->refresh();

        $newGlobalEventGoal = GlobalEventGoal::where('event_type', $event->type)->first();

        $this->assertEquals(GlobalEventSteps::BATTLE, $event->current_event_goal_step);

        $expectedAttributes = GlobalEventForEventTypeValue::returnGlobalEventInfoForSeasonalEvents($event->type);
        $actualAttributes   = $newGlobalEventGoal->toArray();
        $actualAttributes   = array_intersect_key($actualAttributes, $expectedAttributes);

        $this->assertEquals($expectedAttributes, $actualAttributes);
        $this->assertEmpty(GlobalEventCraftingInventory::all());
        $this->assertEmpty(GlobalEventCraftingInventorySlot::all());
    }

    public function testHandleMovingFromCraftEventStepToEnchantingStep() {

        $this->cleanUpPreviousEvents();

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_goal_steps' => [GlobalEventSteps::BATTLE, GlobalEventSteps::CRAFT, GlobalEventSteps::ENCHANT],
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
        ]);

        $eventGoal = $this->createGlobalEventGoal([
            'max_crafts'                     => 1000,
            'reward_every'                   => 100,
            'next_reward_at'                 => 100,
            'event_type'                     => $event->type,
            'item_specialty_type_reward'     => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique'               => true,
            'unique_type'                    => RandomAffixDetails::LEGENDARY,
            'should_be_mythic'               => false,
        ]);

        $this->createGlobalEventCrafts([
            'global_event_goal_id' => $eventGoal->id,
            'character_id'         => $this->character->id,
            'crafts'                => 1000,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $eventGoal->id,
            'character_id'         => $eventGoal->id,
            'current_crafts'       => 1000,
        ]);

        $inventory = GlobalEventCraftingInventory::create([
            'global_event_id' => $eventGoal->id,
            'character_id'    => $this->character->id,
        ]);

        GlobalEventCraftingInventorySlot::create([
            'global_event_crafting_inventory_id' => $inventory->id,
            'item_id' => 5,
        ]);

        $this->artisan('restart:global-event-goal');

        $event = $event->refresh();

        $newGlobalEventGoal = GlobalEventGoal::where('event_type', $event->type)->first();

        $this->assertEquals(GlobalEventSteps::ENCHANT, $event->current_event_goal_step);

        $expectedAttributes = GlobalEventForEventTypeValue::returnEnchantingEventGoal($event->type);
        $actualAttributes   = $newGlobalEventGoal->toArray();
        $actualAttributes   = array_intersect_key($actualAttributes, $expectedAttributes);

        $this->assertEquals($expectedAttributes, $actualAttributes);
        $this->assertNotEmpty(GlobalEventCraftingInventory::all());
        $this->assertNotEmpty(GlobalEventCraftingInventorySlot::all());
    }

    public function testHandleMovingToCraftingStepOfEventGoal() {

        $this->cleanUpPreviousEvents();

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_goal_steps' => [GlobalEventSteps::BATTLE, GlobalEventSteps::CRAFT, GlobalEventSteps::ENCHANT],
            'current_event_goal_step' => GlobalEventSteps::BATTLE,
        ]);

        $eventGoal = $this->createGlobalEventGoal([
            'max_kills'                      => 1000,
            'reward_every'                   => 100,
            'next_reward_at'                 => 100,
            'event_type'                     => $event->type,
            'item_specialty_type_reward'     => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique'               => true,
            'unique_type'                    => RandomAffixDetails::LEGENDARY,
            'should_be_mythic'               => false,
        ]);

        $this->createGlobalEventKill([
            'global_event_goal_id' => $eventGoal->id,
            'character_id'         => $this->character->id,
            'kills'                => 1000,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $eventGoal->id,
            'character_id'         => $eventGoal->id,
            'current_kills'        => 1000,
        ]);

        $this->artisan('restart:global-event-goal');

        $event = $event->refresh();

        $newGlobalEventGoal = GlobalEventGoal::where('event_type', $event->type)->first();

        $this->assertEquals(GlobalEventSteps::CRAFT, $event->current_event_goal_step);

        $expectedAttributes = GlobalEventForEventTypeValue::returnCraftingEventGoal($event->type);
        $actualAttributes   = $newGlobalEventGoal->toArray();
        $actualAttributes   = array_intersect_key($actualAttributes, $expectedAttributes);

        $this->assertEquals($expectedAttributes, $actualAttributes);
    }
}
