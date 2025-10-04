<?php

namespace Tests\Unit\Game\Skills\Handlers;

use App\Flare\Models\GlobalEventCraftingInventory;
use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\WeaponTypes;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Handlers\HandleUpdatingCraftingGlobalEventGoal;
use App\Game\Skills\Values\SkillTypeValue;
use Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Traits\CreateItem;

class HandleUpdatingCraftingGlobalEventGoalTest extends TestCase
{
    use CreateEvent, CreateGameSkill, CreateGlobalEventGoal, CreateItem, RefreshDatabase;

    private ?HandleUpdatingCraftingGlobalEventGoal $handleUpdatingCraftingGlobalEventGoal;

    private ?CharacterFactory $character;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handleUpdatingCraftingGlobalEventGoal = resolve(HandleUpdatingCraftingGlobalEventGoal::class);

        $this->character = (new CharacterFactory)->createBaseCharacter()->assignSkill(
            $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value]),
            400
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->handleUpdatingCraftingGlobalEventGoal = null;
        $this->character = null;
    }

    public function test_do_not_participate_in_crafting_global_event_when_event_does_not_exist()
    {
        $item = $this->createItem(['type' => WeaponTypes::WEAPON]);
        $character = $this->character->getCharacter();

        $this->handleUpdatingCraftingGlobalEventGoal->handleUpdatingCraftingGlobalEventGoal($character, $item);

        $character = $character->refresh();

        $this->assertNull($character->globalEventCrafts);

        $this->assertNull($character->globalEventParticipation);

        $this->assertEmpty(GlobalEventCraftingInventory::all());
    }

    public function test_do_not_participate_in_crafting_global_event_when_global_event_does_not_exist()
    {
        $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
        ]);

        $item = $this->createItem(['type' => WeaponTypes::WEAPON]);
        $character = $this->character->getCharacter();

        $this->handleUpdatingCraftingGlobalEventGoal->handleUpdatingCraftingGlobalEventGoal($character, $item);

        $character = $character->refresh();

        $this->assertNull($character->globalEventCrafts);

        $this->assertNull($character->globalEventParticipation);

        $this->assertEmpty(GlobalEventCraftingInventory::all());
    }

    public function test_participate_in_global_crafting_event()
    {
        $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
        ]);

        $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'max_crafts' => 100,
            'reward_every' => 10,
            'next_reward_at' => 10,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => false,
            'should_be_mythic' => true,
        ]);

        $item = $this->createItem(['type' => WeaponTypes::WEAPON]);
        $character = $this->character->getCharacter();

        $this->handleUpdatingCraftingGlobalEventGoal->handleUpdatingCraftingGlobalEventGoal($character, $item);

        $character = $character->refresh();

        $this->assertNotNull($character->globalEventCrafts);
        $this->assertNotNull($character->globalEventParticipation);
        $this->assertNotEmpty(GlobalEventCraftingInventory::all());

        $this->assertEquals(1, $character->globalEventCrafts->crafts);
        $this->assertEquals(1, $character->globalEventParticipation->current_crafts);

        $this->assertCount(1, GlobalEventCraftingInventorySlot::where('item_id', $item->id)->get());
    }

    public function test_participate_in_event_when_max_crafts_are_reached()
    {
        Event::fake();

        $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
        ]);

        $globalEvent = $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'max_crafts' => 100,
            'reward_every' => 10,
            'next_reward_at' => 10,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => false,
            'should_be_mythic' => true,
        ]);

        $item = $this->createItem(['type' => WeaponTypes::WEAPON]);
        $character = $this->character->getCharacter();

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

        $this->assertNull($character->globalEventCrafts);
        $this->assertEmpty(GlobalEventCraftingInventory::all());
    }

    public function test_participate_in_global_crafting_event_when_we_should_be_rewarded()
    {
        $this->createItem(['specialty_type' => ItemSpecialtyType::DELUSIONAL_SILVER]);

        $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
        ]);

        $eventGoal = $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'max_crafts' => 100,
            'reward_every' => 10,
            'next_reward_at' => 10,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => false,
            'should_be_mythic' => true,
        ]);

        $character = $this->character->getCharacter();

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

        $this->assertNotNull($character->globalEventCrafts);
        $this->assertNotNull($character->globalEventParticipation);
        $this->assertNotEmpty(GlobalEventCraftingInventory::all());

        $this->assertEquals(100, $character->globalEventCrafts->crafts);
        $this->assertEquals(100, $character->globalEventParticipation->current_crafts);

        $this->assertCount(1, GlobalEventCraftingInventorySlot::where('item_id', $item->id)->get());

        $foundMythic = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->is_mythic;
        })->first();

        $this->assertNotNull($foundMythic);
    }
}
