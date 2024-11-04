<?php

namespace Tests\Unit\Game\Events\Values;

use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventForEventTypeValue;
use Tests\TestCase;

class GlobalEventForEventTypeValueTest extends TestCase
{
    public function testGetWinterEventGlobalEventGoalData()
    {
        $expected = [
            'max_kills' => 190000,
            'reward_every' => 10000,
            'next_reward_at' => 10000,
            'event_type' => EventType::WINTER_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ];

        $this->assertEquals($expected, GlobalEventForEventTypeValue::returnGlobalEventInfoForSeasonalEvents(EventType::WINTER_EVENT));
    }

    public function testGetDelusionalMemoriesBattleEventGoalData()
    {
        $expected = [
            'max_kills' => 400000,
            'reward_every' => 20000,
            'next_reward_at' => 20000,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => false,
            'unique_type' => RandomAffixDetails::MYTHIC,
            'should_be_mythic' => true,
        ];

        $this->assertEquals($expected, GlobalEventForEventTypeValue::returnGlobalEventInfoForSeasonalEvents(EventType::DELUSIONAL_MEMORIES_EVENT));
    }

    public function testGetCraftingEventGoalData()
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

    public function testGetEnchantingEventGoalData()
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

    public function testGetDelusionalMemoriesGlobalEventSteps()
    {
        $expected = [
            'battle',
            'craft',
            'enchant',
        ];

        $this->assertEquals($expected, GlobalEventForEventTypeValue::fetchDelusionalMemoriesGlobalEventSteps());
    }

    public function testGetNothingForGlobalEventGoals()
    {
        $this->assertEmpty(GlobalEventForEventTypeValue::returnGlobalEventInfoForSeasonalEvents(EventType::WEEKLY_CELESTIALS));
    }
}
