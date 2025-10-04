<?php

namespace Tests\Unit\Game\Events\Values;

use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventForEventTypeValue;
use Tests\TestCase;

class GlobalEventForEventTypeValueTest extends TestCase
{
    public function test_get_winter_event_global_event_goal_data()
    {
        $expected = [
            'max_kills' => 20000,
            'reward_every' => 2000,
            'next_reward_at' => 2000,
            'event_type' => EventType::WINTER_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ];

        $this->assertEquals($expected, GlobalEventForEventTypeValue::returnGlobalEventInfoForSeasonalEvents(EventType::WINTER_EVENT));
    }

    public function test_get_delusional_memories_battle_event_goal_data()
    {
        $expected = [
            'max_kills' => 20000,
            'reward_every' => 2000,
            'next_reward_at' => 2000,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => false,
            'unique_type' => RandomAffixDetails::MYTHIC,
            'should_be_mythic' => true,
        ];

        $this->assertEquals($expected, GlobalEventForEventTypeValue::returnGlobalEventInfoForSeasonalEvents(EventType::DELUSIONAL_MEMORIES_EVENT));
    }

    public function test_get_crafting_event_goal_data()
    {
        $expected = [
            'max_crafts' => 500,
            'reward_every' => 100,
            'next_reward_at' => 100,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ];

        $this->assertEquals($expected, GlobalEventForEventTypeValue::returnDelusionalMemoriesCraftingEventGoal());
    }

    public function test_get_enchanting_event_goal_data()
    {
        $expected = [
            'max_enchants' => 500,
            'reward_every' => 100,
            'next_reward_at' => 100,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ];

        $this->assertEquals($expected, GlobalEventForEventTypeValue::returnDelusionalMemoriesEnchantingEventGoal());
    }

    public function test_get_delusional_memories_global_event_steps()
    {
        $expected = [
            'battle',
            'craft',
            'enchant',
        ];

        $this->assertEquals($expected, GlobalEventForEventTypeValue::fetchDelusionalMemoriesGlobalEventSteps());
    }

    public function test_get_nothing_for_global_event_goals()
    {
        $this->assertEmpty(GlobalEventForEventTypeValue::returnGlobalEventInfoForSeasonalEvents(EventType::WEEKLY_CELESTIALS));
    }
}
