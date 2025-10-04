<?php

namespace Tests\Unit\Game\Events\Values;

use App\Game\Events\Values\EventType;
use Exception;
use Tests\TestCase;

class EventTypeTest extends TestCase
{
    public function test_invalid_event_type()
    {
        $this->expectException(Exception::class);

        new EventType(905);
    }

    public function test_get_options_for_select()
    {
        $expected = [
            EventType::WEEKLY_CELESTIALS => 'Weekly Celestials',
            EventType::WEEKLY_CURRENCY_DROPS => 'Weekly Currency Drops',
            EventType::RAID_EVENT => 'Raid Event',
            EventType::WINTER_EVENT => 'Winter Event',
            EventType::PURGATORY_SMITH_HOUSE => 'Purgatory Smith House',
            EventType::GOLD_MINES => 'Gold Mines',
            EventType::THE_OLD_CHURCH => 'The Old Church',
            EventType::DELUSIONAL_MEMORIES_EVENT => 'Delusional Memories Event',
            EventType::WEEKLY_FACTION_LOYALTY_EVENT => 'Weekly Faction Loyalty Event',
            EventType::FEEDBACK_EVENT => 'Tlessa\'s Feedback Event',

        ];

        $this->assertEquals($expected, EventType::getOptionsForSelect());
    }

    public function test_is_weekly_celestials()
    {
        $this->assertTrue((new EventType(EventType::WEEKLY_CELESTIALS))->isWeeklyCelestials());
    }

    public function test_is_weekly_currencies()
    {
        $this->assertTrue((new EventType(EventType::WEEKLY_CURRENCY_DROPS))->isWeeklyCurrencyDrops());
    }

    public function test_is_raid_event()
    {
        $this->assertTrue((new EventType(EventType::RAID_EVENT))->isRaidEvent());
    }

    public function test_is_winter_event()
    {
        $this->assertTrue((new EventType(EventType::WINTER_EVENT))->isWinterEvent());
    }

    public function test_is_purgatory_smiths_house()
    {
        $this->assertTrue((new EventType(EventType::PURGATORY_SMITH_HOUSE))->isPurgatorySmithHouseEvent());
    }

    public function test_is_gold_mines()
    {
        $this->assertTrue((new EventType(EventType::GOLD_MINES))->isGoldMinesEvent());
    }

    public function test_is_the_old_church()
    {
        $this->assertTrue((new EventType(EventType::THE_OLD_CHURCH))->isTheOldChurchEvent());
    }

    public function test_is_weekly_faction_loyalty_event()
    {
        $this->assertTrue((new EventType(EventType::WEEKLY_FACTION_LOYALTY_EVENT))->isWeeklyFactionLoyaltyEvent());
    }

    public function test_is_feed_back_event()
    {
        $this->assertTrue((new EventType(EventType::FEEDBACK_EVENT))->isFeedbackEvent());
    }
}
