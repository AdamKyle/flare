<?php

namespace Tests\Unit\Game\Events\Jobs;

use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventForEventTypeValue;
use Tests\TestCase;

class GlobalEventForEventTypeValueTest extends TestCase {

    public function testGetWinterEventGlobalEventGoalData() {
        $expected = [
            'max_kills'                  => 19000,
            'reward_every_kills'         => 1000,
            'next_reward_at'             => 1000,
            'event_type'                 => EventType::WINTER_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique'           => true,
            'unique_type'                => RandomAffixDetails::LEGENDARY,
            'should_be_mythic'           => false,
        ];

        $this->assertEquals($expected, GlobalEventForEventTypeValue::returnGlobalEventInfoForSeasonalEvents(EventType::WINTER_EVENT));
    }

    public function testGetNothingForGlobalEventGoals() {
        $this->assertEmpty(GlobalEventForEventTypeValue::returnGlobalEventInfoForSeasonalEvents(EventType::MONTHLY_PVP));
    }
}
