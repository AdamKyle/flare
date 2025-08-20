<?php

namespace Tests\Unit\Game\Events\Services;

use App\Flare\Models\GlobalEventCraftingInventory;
use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\GlobalEventParticipation;
use App\Flare\Models\GlobalEventKill;
use App\Flare\Models\GlobalEventCraft;
use App\Flare\Models\GlobalEventEnchant;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Events\Services\GlobalEventStepRotatorService;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventForEventTypeValue;
use App\Game\Events\Values\GlobalEventSteps;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Setup\Character\CharacterFactory;

class GlobalEventStepRotatorServiceTest extends TestCase
{
    use RefreshDatabase, CreateEvent, CreateGlobalEventGoal;

    /**
     * Returns null when current step is not found in steps (no side effects).
     */
    public function testReturnsNullWhenStepNotInSteps(): void
    {
        $event = $this->createEvent([
            'type'                => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_goal_steps'    => [GlobalEventSteps::BATTLE, GlobalEventSteps::CRAFT, GlobalEventSteps::ENCHANT],
            'current_event_goal_step' => 'apples',
        ]);

        // Seed one row to prove nothing is purged.
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $goal = $this->createGlobalEventGoal([
            'max_kills'                 => 1000,
            'reward_every'              => 100,
            'next_reward_at'            => 100,
            'event_type'                => $event->type,
            'item_specialty_type_reward'=> ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique'          => true,
            'unique_type'               => RandomAffixDetails::LEGENDARY,
            'should_be_mythic'          => false,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $goal->id,
            'character_id'         => $character->id,
            'current_kills'        => 10,
        ]);

        /** @var GlobalEventStepRotatorService $rotator */
        $rotator = app(GlobalEventStepRotatorService::class);

        $result = $rotator->rotate($event->refresh());
        $this->assertNull($result);

        // Assert nothing was truncated
        $this->assertNotEmpty(GlobalEventGoal::all());
        $this->assertNotEmpty(GlobalEventParticipation::all());
    }

    /**
     * Rotates from BATTLE -> CRAFT:
     * - core tables purged
     * - new goal matches crafting template
     * - event step updated to CRAFT
     */
    public function testRotateFromBattleToCraft(): void
    {
        $event = $this->createEvent([
            'type'                     => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_goal_steps'         => [GlobalEventSteps::BATTLE, GlobalEventSteps::CRAFT, GlobalEventSteps::ENCHANT],
            'current_event_goal_step'  => GlobalEventSteps::BATTLE,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $goal = $this->createGlobalEventGoal([
            'max_kills'                 => 1000,
            'reward_every'              => 100,
            'next_reward_at'            => 100,
            'event_type'                => $event->type,
            'item_specialty_type_reward'=> ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique'          => true,
            'unique_type'               => RandomAffixDetails::LEGENDARY,
            'should_be_mythic'          => false,
        ]);

        // Seed core rows that should be purged.
        $this->createGlobalEventKill([
            'global_event_goal_id' => $goal->id,
            'character_id'         => $character->id,
            'kills'                => 50,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $goal->id,
            'character_id'         => $character->id,
            'current_kills'        => 50,
        ]);

        $this->createGlobalEventCrafts([
            'global_event_goal_id' => $goal->id,
            'character_id'         => $character->id,
            'crafts'               => 1,
        ]);

        $this->createGlobalEventEnchants([
            'global_event_goal_id' => $goal->id,
            'character_id'         => $character->id,
            'enchants'             => 1,
        ]);

        /** @var GlobalEventStepRotatorService $rotator */
        $rotator = app(GlobalEventStepRotatorService::class);

        $result = $rotator->rotate($event->refresh());
        $this->assertNotNull($result);

        $this->assertSame(GlobalEventSteps::CRAFT, $result['new_step']);

        // New goal matches crafting template
        $expected = GlobalEventForEventTypeValue::returnDelusionalMemoriesCraftingEventGoal($event->type);
        $actual   = $result['new_goal']->toArray();
        $actual   = array_intersect_key($actual, $expected);
        $this->assertEquals($expected, $actual);

        // Event step updated
        $this->assertSame(GlobalEventSteps::CRAFT, $event->refresh()->current_event_goal_step);

        // Core tables purged
        $this->assertEmpty(GlobalEventParticipation::all());
        $this->assertEmpty(GlobalEventKill::all());
        $this->assertEmpty(GlobalEventCraft::all());
        $this->assertEmpty(GlobalEventEnchant::all());

        // Only the new goal should remain
        $this->assertEquals(1, GlobalEventGoal::count());
        $this->assertTrue($result['new_goal']->is(GlobalEventGoal::first()));
    }

    /**
     * Rotates from CRAFT -> ENCHANT:
     * - core tables purged
     * - new goal matches enchanting template
     * - event step updated to ENCHANT
     */
    public function testRotateFromCraftToEnchant(): void
    {
        $event = $this->createEvent([
            'type'                     => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_goal_steps'         => [GlobalEventSteps::BATTLE, GlobalEventSteps::CRAFT, GlobalEventSteps::ENCHANT],
            'current_event_goal_step'  => GlobalEventSteps::CRAFT,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $goal = $this->createGlobalEventGoal([
            'max_kills'                 => 1000,
            'reward_every'              => 100,
            'next_reward_at'            => 100,
            'event_type'                => $event->type,
            'item_specialty_type_reward'=> ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique'          => true,
            'unique_type'               => RandomAffixDetails::LEGENDARY,
            'should_be_mythic'          => false,
        ]);

        // Seed core rows that should be purged.
        $this->createGlobalEventKill([
            'global_event_goal_id' => $goal->id,
            'character_id'         => $character->id,
            'kills'                => 5,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $goal->id,
            'character_id'         => $character->id,
            'current_kills'        => 5,
        ]);

        /** @var GlobalEventStepRotatorService $rotator */
        $rotator = app(GlobalEventStepRotatorService::class);

        $result = $rotator->rotate($event->refresh());
        $this->assertNotNull($result);

        $this->assertSame(GlobalEventSteps::ENCHANT, $result['new_step']);

        // New goal matches enchanting template
        $expected = GlobalEventForEventTypeValue::returnDelusionalMemoriesEnchantingEventGoal($event->type);
        $actual   = $result['new_goal']->toArray();
        $actual   = array_intersect_key($actual, $expected);
        $this->assertEquals($expected, $actual);

        // Event step updated
        $this->assertSame(GlobalEventSteps::ENCHANT, $event->refresh()->current_event_goal_step);

        // Core tables purged
        $this->assertEmpty(GlobalEventParticipation::all());
        $this->assertEmpty(GlobalEventKill::all());
        $this->assertEmpty(GlobalEventCraft::all());
        $this->assertEmpty(GlobalEventEnchant::all());

        // Only the new goal should remain
        $this->assertEquals(1, GlobalEventGoal::count());
        $this->assertTrue($result['new_goal']->is(GlobalEventGoal::first()));
    }

    /**
     * Rotates from ENCHANT -> wraps to BATTLE:
     * - core tables purged
     * - enchant inventories purged
     * - new goal matches default seasonal template
     * - event step updated to BATTLE
     */
    public function testRotateFromEnchantWrapsToBattleAndPurgesEnchantInventories(): void
    {
        $event = $this->createEvent([
            'type'                     => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_goal_steps'         => [GlobalEventSteps::BATTLE, GlobalEventSteps::CRAFT, GlobalEventSteps::ENCHANT],
            'current_event_goal_step'  => GlobalEventSteps::ENCHANT,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $goal = $this->createGlobalEventGoal([
            'max_kills'                 => 1000,
            'reward_every'              => 100,
            'next_reward_at'            => 100,
            'event_type'                => $event->type,
            'item_specialty_type_reward'=> ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique'          => true,
            'unique_type'               => RandomAffixDetails::LEGENDARY,
            'should_be_mythic'          => false,
        ]);

        // Core rows
        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $goal->id,
            'character_id'         => $character->id,
            'current_enchants'     => 5,
        ]);

        // Enchant inventories (should be purged when current step == ENCHANT)
        $inv = GlobalEventCraftingInventory::create([
            'global_event_id' => $goal->id,
            'character_id'    => $character->id,
        ]);

        GlobalEventCraftingInventorySlot::create([
            'global_event_crafting_inventory_id' => $inv->id,
            'item_id'                             => 1,
        ]);

        /** @var GlobalEventStepRotatorService $rotator */
        $rotator = app(GlobalEventStepRotatorService::class);

        $result = $rotator->rotate($event->refresh());
        $this->assertNotNull($result);

        $this->assertSame(GlobalEventSteps::BATTLE, $result['new_step']);

        // New goal matches default seasonal template
        $expected = GlobalEventForEventTypeValue::returnGlobalEventInfoForSeasonalEvents($event->type);
        $actual   = $result['new_goal']->toArray();
        $actual   = array_intersect_key($actual, $expected);
        $this->assertEquals($expected, $actual);

        // Event step updated
        $this->assertSame(GlobalEventSteps::BATTLE, $event->refresh()->current_event_goal_step);

        // Core tables purged
        $this->assertEmpty(GlobalEventParticipation::all());
        $this->assertEmpty(GlobalEventKill::all());
        $this->assertEmpty(GlobalEventCraft::all());
        $this->assertEmpty(GlobalEventEnchant::all());

        // Enchant inventories also purged
        $this->assertEmpty(GlobalEventCraftingInventory::all());
        $this->assertEmpty(GlobalEventCraftingInventorySlot::all());

        // Only the new goal should remain
        $this->assertEquals(1, GlobalEventGoal::count());
        $this->assertTrue($result['new_goal']->is(GlobalEventGoal::first()));
    }
}
