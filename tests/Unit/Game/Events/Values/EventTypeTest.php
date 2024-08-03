<?php

namespace Tests\Unit\Game\Events\Values;

use App\Game\Events\Values\EventType;
use Exception;
use Tests\TestCase;

class EventTypeTest extends TestCase
{
    public function testInvalidEventType()
    {
        $this->expectException(Exception::class);

        new EventType(905);
    }

    public function testGetOptionsForSelect()
    {
        $expected = [
            EventType::WEEKLY_CELESTIALS => 'Weekly Celestials',
            EventType::MONTHLY_PVP => 'Monthly PVP',
            EventType::WEEKLY_CURRENCY_DROPS => 'Weekly Currency Drops',
            EventType::RAID_EVENT => 'Raid Event',
            EventType::WINTER_EVENT => 'Winter Event',
            EventType::PURGATORY_SMITH_HOUSE => 'Purgatory Smith House',
            EventType::GOLD_MINES => 'Gold Mines',
            EventType::THE_OLD_CHURCH => 'The Old Church',
            EventType::DELUSIONAL_MEMORIES_EVENT => 'Delusional Memories Event',
            EventType::WEEKLY_FACTION_LOYALTY_EVENT => 'Weekly Faction Loyalty Event',
        ];

        $this->assertEquals($expected, EventType::getOptionsForSelect());
    }

    public function testIsWeeklyCelestials()
    {
        $this->assertTrue((new EventType(EventType::WEEKLY_CELESTIALS))->isWeeklyCelestials());
    }

    public function testIsMonthlyPVP()
    {
        $this->assertTrue((new EventType(EventType::MONTHLY_PVP))->isMonthlyPVP());
    }

    public function testIsWeeklyCurrencies()
    {
        $this->assertTrue((new EventType(EventType::WEEKLY_CURRENCY_DROPS))->isWeeklyCurrencyDrops());
    }

    public function testIsRaidEvent()
    {
        $this->assertTrue((new EventType(EventType::RAID_EVENT))->isRaidEvent());
    }

    public function testIsWinterEvent()
    {
        $this->assertTrue((new EventType(EventType::WINTER_EVENT))->isWinterEvent());
    }

    public function testIsPurgatorySmithsHouse()
    {
        $this->assertTrue((new EventType(EventType::PURGATORY_SMITH_HOUSE))->isPurgatorySmithHouseEvent());
    }

    public function testIsGoldMines()
    {
        $this->assertTrue((new EventType(EventType::GOLD_MINES))->isGoldMinesEvent());
    }

    public function testIsTheOldChurch()
    {
        $this->assertTrue((new EventType(EventType::THE_OLD_CHURCH))->isTheOldChurchEvent());
    }

    public function testIsWeeklyFactionLoyaltyEvent()
    {
        $this->assertTrue((new EventType(EventType::WEEKLY_FACTION_LOYALTY_EVENT))->isWeeklyFactionLoyaltyEvent());
    }
}
