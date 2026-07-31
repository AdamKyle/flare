<?php

namespace Tests\Unit\Game\Skills\Handlers;

use App\Flare\Models\GlobalEventCraftingInventory;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\WeaponTypes;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Events\Values\ScheduledEventStatus;
use App\Game\Skills\Handlers\HandleUpdatingEnchantingGlobalEventGoal;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateScheduledEvent;

class HandleUpdatingEnchantingGlobalEventGoalTest extends TestCase
{
    use CreateEvent, CreateGameMap, CreateGameSkill, CreateGlobalEventGoal, CreateItem, CreateItemAffix, CreateScheduledEvent, RefreshDatabase;

    private ?HandleUpdatingEnchantingGlobalEventGoal $handleUpdateEnchantingGlobalEventGoal;

    private ?CharacterFactory $characterFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handleUpdateEnchantingGlobalEventGoal = resolve(HandleUpdatingEnchantingGlobalEventGoal::class);

        $this->character = (new CharacterFactory)->createBaseCharacter()->assignSkill(
            $this->createGameSkill(['name' => 'Enchanting', 'type' => SkillTypeValue::ENCHANTING->value]),
            400
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->handleUpdateEnchantingGlobalEventGoal = null;
        $this->character = null;
    }

    public function test_do_not_participate_in_enchanting_global_event_when_event_does_not_exist()
    {
        $item = $this->createItem(['type' => WeaponTypes::WEAPON, 'item_prefix_id' => $this->createItemAffix(['type' => 'prefix'])->id]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $slot = $character->inventory->slots->first();

        $this->handleUpdateEnchantingGlobalEventGoal->handleUpdatingEnchantingGlobalEventGoal($character, $slot);

        $character = $character->refresh();

        $this->assertEmpty($character->globalEventEnchants);

        $this->assertEmpty($character->globalEventParticipation);

        $this->assertEmpty(GlobalEventCraftingInventory::all());

        $this->assertNotEmpty($character->inventory->slots);
    }

    public function test_do_not_participate_in_enchanting_global_event_when_global_event_does_not_exist()
    {
        $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'current_event_goal_step' => GlobalEventSteps::ENCHANT,
        ]);

        $item = $this->createItem(['type' => WeaponTypes::WEAPON, 'item_prefix_id' => $this->createItemAffix(['type' => 'prefix'])->id]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $slot = $character->inventory->slots->first();

        $this->handleUpdateEnchantingGlobalEventGoal->handleUpdatingEnchantingGlobalEventGoal($character, $slot);

        $character = $character->refresh();

        $this->assertEmpty($character->globalEventEnchants);

        $this->assertEmpty($character->globalEventParticipation);

        $this->assertNotEmpty($character->inventory->slots);
    }

    public function test_participate_in_global_enchanting_event()
    {
        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::ENCHANT,
        ]);

        $goal = $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_enchants' => 100,
            'reward_every' => 10,
            'next_reward_at' => 10,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => false,
            'should_be_mythic' => true,
        ]);

        $map = $this->createGameMap(['only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);

        $item = $this->createItem(['type' => WeaponTypes::WEAPON, 'item_prefix_id' => $this->createItemAffix(['type' => 'prefix'])->id]);

        $character = $this->character->givePlayerLocation(16, 16, $map)->inventoryManagement()->giveItem($item)->getCharacter();

        $slot = $character->inventory->slots->first();

        $this->handleUpdateEnchantingGlobalEventGoal->handleUpdatingEnchantingGlobalEventGoal($character, $slot);

        $character = $character->refresh();

        $this->assertNotEmpty($character->globalEventEnchants);
        $this->assertNotEmpty($character->globalEventParticipation);

        $this->assertEquals(1, $character->globalEventEnchants()->where('global_event_goal_id', $goal->id)->first()->enchants);
        $this->assertEquals(1, $character->globalEventParticipation()->where('global_event_goal_id', $goal->id)->first()->current_enchants);

        $this->assertEmpty($character->inventory->slots);
    }

    public function test_participate_in_global_enchanting_event_when_we_should_be_rewarded()
    {
        $this->createItem(['name' => 'Delusional silver', 'specialty_type' => ItemSpecialtyType::DELUSIONAL_SILVER]);

        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::ENCHANT,
        ]);

        $eventGoal = $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_enchants' => 100,
            'reward_every' => 10,
            'next_reward_at' => 10,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => false,
            'should_be_mythic' => true,
        ]);

        $map = $this->createGameMap(['only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);

        $item = $this->createItem(['name' => 'Item To Enchant', 'type' => WeaponTypes::WEAPON, 'item_prefix_id' => $this->createItemAffix(['type' => 'prefix'])->id]);

        $character = $this->character->givePlayerLocation(16, 16, $map)->inventoryManagement()->giveItem($item)->getCharacter();

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $character->id,
            'current_enchants' => 99,
        ]);

        $character->globalEventEnchants()->create([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $character->id,
            'enchants' => 99,
        ]);

        $character = $character->refresh();

        $slot = $character->inventory->slots->first();

        $this->handleUpdateEnchantingGlobalEventGoal->handleUpdatingEnchantingGlobalEventGoal($character, $slot);

        $character = $character->refresh();

        $this->assertNotEmpty($character->globalEventEnchants);
        $this->assertNotEmpty($character->globalEventParticipation);

        $this->assertEquals(100, $character->globalEventEnchants()->where('global_event_goal_id', $eventGoal->id)->first()->enchants);
        $this->assertEquals(100, $character->globalEventParticipation()->where('global_event_goal_id', $eventGoal->id)->first()->current_enchants);

        $foundMythic = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->is_mythic;
        })->first();

        $this->assertNotNull($foundMythic);

        $this->assertCount(1, $character->inventory->slots);

        $this->assertTrue($this->handleUpdateEnchantingGlobalEventGoal->handedOverItem());
    }
}
