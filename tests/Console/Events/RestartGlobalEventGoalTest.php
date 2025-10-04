<?php

namespace Tests\Console\Events;

use App\Flare\Models\Character;
use App\Flare\Models\GlobalEventCraftingInventory;
use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\MapNameValue;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventForEventTypeValue;
use App\Game\Events\Values\GlobalEventSteps;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGlobalEventGoal;

class RestartGlobalEventGoalTest extends TestCase
{
    use CreateEvent, CreateGameMap, CreateGlobalEventGoal, RefreshDatabase;

    private ?Character $character;

    protected function setUp(): void
    {

        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
    }

    protected function tearDown(): void
    {

        parent::tearDown();

        $this->character = null;
    }

    public function test_reset_event_goal()
    {

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        $map = $this->createGameMap([
            'name' => MapNameValue::DELUSIONAL_MEMORIES,
            'only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $map);

        $eventGoal = $this->createGlobalEventGoal([
            'max_kills' => 1000,
            'reward_every' => 100,
            'next_reward_at' => 100,
            'event_type' => $event->type,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $this->createGlobalEventKill([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $this->character->id,
            'kills' => 1000,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $this->character->id,
            'current_kills' => 1000,
        ]);

        $this->artisan('restart:global-event-goal');

        $eventGoal = $eventGoal->refresh();

        $this->assertEmpty($eventGoal->globalEventParticipation);
        $this->assertEmpty($eventGoal->globalEventKills);
    }

    public function test_do_not_reset_event_goal_when_max_kills_do_match_current_kills()
    {

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        $map = $this->createGameMap([
            'name' => MapNameValue::DELUSIONAL_MEMORIES,
            'only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $map);

        $eventGoal = $this->createGlobalEventGoal([
            'max_kills' => 1000,
            'reward_every' => 100,
            'next_reward_at' => 100,
            'event_type' => $event->type,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $this->createGlobalEventKill([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $this->character->id,
            'kills' => 1000,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $this->character->id,
            'current_kills' => 999,
        ]);

        $this->artisan('restart:global-event-goal');

        $eventGoal = $eventGoal->refresh();

        $this->assertNotEmpty($eventGoal->globalEventParticipation);
        $this->assertNotEmpty($eventGoal->globalEventKills);
    }

    public function test_do_not_rest_event_goal_when_no_global_event_goal_exists()
    {

        $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        $this->artisan('restart:global-event-goal');

        $this->assertNull(GlobalEventGoal::first());
    }

    public function test_do_not_rest_event_goal()
    {

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        $eventGoal = $this->createGlobalEventGoal([
            'max_kills' => 1000,
            'reward_every' => 100,
            'next_reward_at' => 100,
            'event_type' => $event->type,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $this->createGlobalEventKill([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $this->character->id,
            'kills' => 10,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $this->character->id,
            'current_kills' => 10,
        ]);

        $this->artisan('restart:global-event-goal');

        $eventGoal = $eventGoal->refresh();

        $this->assertNotEmpty($eventGoal->globalEventParticipation);
        $this->assertNotEmpty($eventGoal->globalEventKills);
    }

    public function test_do_not_reset_crafting_goal()
    {

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        $eventGoal = $this->createGlobalEventGoal([
            'max_crafts' => 1000,
            'reward_every' => 100,
            'next_reward_at' => 100,
            'event_type' => $event->type,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $this->createGlobalEventCrafts([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $this->character->id,
            'crafts' => 10,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $this->character->id,
            'current_crafts' => 10,
        ]);

        $this->artisan('restart:global-event-goal');

        $eventGoal = $eventGoal->refresh();

        $this->assertNotEmpty($eventGoal->globalEventParticipation);
        $this->assertNotEmpty($eventGoal->globalEventCrafts);
    }

    public function test_do_not_reset_enchanting_goal()
    {

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        $eventGoal = $this->createGlobalEventGoal([
            'max_enchants' => 1000,
            'reward_every' => 100,
            'next_reward_at' => 100,
            'event_type' => $event->type,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $this->createGlobalEventEnchants([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $this->character->id,
            'enchants' => 10,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $this->character->id,
            'current_enchants' => 10,
        ]);

        $this->artisan('restart:global-event-goal');

        $eventGoal = $eventGoal->refresh();

        $this->assertNotEmpty($eventGoal->globalEventParticipation);
        $this->assertNotEmpty($eventGoal->globalEventEnchants);
    }

    public function test_fail_to_move_to_next_step_when_event_does_not_exist()
    {

        $eventGoal = $this->createGlobalEventGoal([
            'max_enchants' => 1000,
            'reward_every' => 100,
            'next_reward_at' => 100,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $this->createGlobalEventEnchants([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $this->character->id,
            'enchants' => 1000,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $eventGoal->id,
            'current_enchants' => 1000,
        ]);

        $inventory = GlobalEventCraftingInventory::create([
            'global_event_id' => $eventGoal->id,
            'character_id' => $this->character->id,
        ]);

        GlobalEventCraftingInventorySlot::create([
            'global_event_crafting_inventory_id' => $inventory->id,
            'item_id' => 5,
        ]);

        $this->artisan('restart:global-event-goal');

        $newGlobalEventGoal = GlobalEventGoal::where('event_type', EventType::DELUSIONAL_MEMORIES_EVENT)->first();

        $expectedAttributes = GlobalEventForEventTypeValue::returnGlobalEventInfoForSeasonalEvents(EventType::DELUSIONAL_MEMORIES_EVENT);
        $actualAttributes = $newGlobalEventGoal->toArray();
        $actualAttributes = array_intersect_key($actualAttributes, $expectedAttributes);

        $this->assertNotEquals($expectedAttributes, $actualAttributes);
        $this->assertNotEmpty(GlobalEventCraftingInventory::all());
        $this->assertNotEmpty(GlobalEventCraftingInventorySlot::all());
    }

    public function test_cannot_move_to_next_step_when_next_step_is_not_a_valid_step()
    {

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_goal_steps' => [GlobalEventSteps::BATTLE, GlobalEventSteps::CRAFT, GlobalEventSteps::ENCHANT],
            'current_event_goal_step' => 'apples',
        ]);

        $eventGoal = $this->createGlobalEventGoal([
            'max_enchants' => 1000,
            'reward_every' => 100,
            'next_reward_at' => 100,
            'event_type' => $event->type,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $this->createGlobalEventEnchants([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $this->character->id,
            'enchants' => 1000,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $eventGoal->id,
            'current_enchants' => 1000,
        ]);

        $inventory = GlobalEventCraftingInventory::create([
            'global_event_id' => $eventGoal->id,
            'character_id' => $this->character->id,
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
        $actualAttributes = $newGlobalEventGoal->toArray();
        $actualAttributes = array_intersect_key($actualAttributes, $expectedAttributes);

        $this->assertNotEquals($expectedAttributes, $actualAttributes);
        $this->assertNotEmpty(GlobalEventCraftingInventory::all());
        $this->assertNotEmpty(GlobalEventCraftingInventorySlot::all());
    }

    public function test_handle_moving_to_the_next_step_from_enchanting_to_battling()
    {

        $this->createGameMap([
            'only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_goal_steps' => [GlobalEventSteps::BATTLE, GlobalEventSteps::CRAFT, GlobalEventSteps::ENCHANT],
            'current_event_goal_step' => GlobalEventSteps::ENCHANT,
        ]);

        $eventGoal = $this->createGlobalEventGoal([
            'max_enchants' => 1000,
            'reward_every' => 100,
            'next_reward_at' => 100,
            'event_type' => $event->type,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $this->createGlobalEventEnchants([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $this->character->id,
            'enchants' => 1000,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $eventGoal->id,
            'current_enchants' => 1000,
        ]);

        $inventory = GlobalEventCraftingInventory::create([
            'global_event_id' => $eventGoal->id,
            'character_id' => $this->character->id,
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
        $actualAttributes = $newGlobalEventGoal->toArray();
        $actualAttributes = array_intersect_key($actualAttributes, $expectedAttributes);

        $this->assertEquals($expectedAttributes, $actualAttributes);
        $this->assertEmpty(GlobalEventCraftingInventory::all());
        $this->assertEmpty(GlobalEventCraftingInventorySlot::all());
    }

    public function test_handle_moving_from_craft_event_step_to_enchanting_step()
    {
        $this->createGameMap([
            'only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_goal_steps' => [GlobalEventSteps::BATTLE, GlobalEventSteps::CRAFT, GlobalEventSteps::ENCHANT],
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
        ]);

        $eventGoal = $this->createGlobalEventGoal([
            'max_crafts' => 1000,
            'reward_every' => 100,
            'next_reward_at' => 100,
            'event_type' => $event->type,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $this->createGlobalEventCrafts([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $this->character->id,
            'crafts' => 1000,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $eventGoal->id,
            'current_crafts' => 1000,
        ]);

        $inventory = GlobalEventCraftingInventory::create([
            'global_event_id' => $eventGoal->id,
            'character_id' => $this->character->id,
        ]);

        GlobalEventCraftingInventorySlot::create([
            'global_event_crafting_inventory_id' => $inventory->id,
            'item_id' => 5,
        ]);

        $this->artisan('restart:global-event-goal');

        $event = $event->refresh();

        $newGlobalEventGoal = GlobalEventGoal::where('event_type', $event->type)->first();

        $this->assertEquals(GlobalEventSteps::ENCHANT, $event->current_event_goal_step);

        $expectedAttributes = GlobalEventForEventTypeValue::returnDelusionalMemoriesEnchantingEventGoal($event->type);
        $actualAttributes = $newGlobalEventGoal->toArray();
        $actualAttributes = array_intersect_key($actualAttributes, $expectedAttributes);

        $this->assertEquals($expectedAttributes, $actualAttributes);
        $this->assertNotEmpty(GlobalEventCraftingInventory::all());
        $this->assertNotEmpty(GlobalEventCraftingInventorySlot::all());
    }

    public function test_handle_moving_to_crafting_step_of_event_goal()
    {
        $this->createGameMap([
            'only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_goal_steps' => [GlobalEventSteps::BATTLE, GlobalEventSteps::CRAFT, GlobalEventSteps::ENCHANT],
            'current_event_goal_step' => GlobalEventSteps::BATTLE,
        ]);

        $eventGoal = $this->createGlobalEventGoal([
            'max_kills' => 1000,
            'reward_every' => 100,
            'next_reward_at' => 100,
            'event_type' => $event->type,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $this->createGlobalEventKill([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $this->character->id,
            'kills' => 1000,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $eventGoal->id,
            'current_kills' => 1000,
        ]);

        $this->artisan('restart:global-event-goal');

        $event = $event->refresh();

        $newGlobalEventGoal = GlobalEventGoal::where('event_type', $event->type)->first();

        $this->assertEquals(GlobalEventSteps::CRAFT, $event->current_event_goal_step);

        $expectedAttributes = GlobalEventForEventTypeValue::returnDelusionalMemoriesCraftingEventGoal($event->type);
        $actualAttributes = $newGlobalEventGoal->toArray();
        $actualAttributes = array_intersect_key($actualAttributes, $expectedAttributes);

        $this->assertEquals($expectedAttributes, $actualAttributes);
    }
}
