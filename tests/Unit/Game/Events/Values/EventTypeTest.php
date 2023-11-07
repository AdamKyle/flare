<?php

namespace Tests\Unit\Game\Events\Values;

use Exception;
use App\Game\Events\Values\EventType;
use Tests\TestCase;

class EventTypeTest extends TestCase {

    public function testInvalidEventType() {
        $this->expectException(Exception::class);

        new EventType(905);
    }

    public function testGetOptionsForSelect() {
        $expected = [
            EventType::WEEKLY_CELESTIALS => 'Weekly Celestials',
            EventType::MONTHLY_PVP => 'Monthly PVP',
            EventType::WEEKLY_CURRENCY_DROPS => 'Weekly Currency Drops',
            EventType::RAID_EVENT => 'Raid Event',
            EventType::WINTER_EVENT => 'Winter Event',
        ];

        $this->assertEquals($expected, EventType::getOptionsForSelect());
    }

    public function testIsWeeklyCelestials() {
        $this->assertTrue((new EventType(EventType::WEEKLY_CELESTIALS))->isWeeklyCelestials());
    }

    public function testIsMonthlyPVP() {
        $this->assertTrue((new EventType(EventType::MONTHLY_PVP))->isMonthlyPVP());
    }

    public function testIsWeeklyCurrencies() {
        $this->assertTrue((new EventType(EventType::WEEKLY_CURRENCY_DROPS))->isWeeklyCurrencyDrops());
    }

    public function testIsRaidEvent() {
        $this->assertTrue((new EventType(EventType::RAID_EVENT))->isRaidEvent());
    }

    public function testIsWinterEvent() {
        $this->assertTrue((new EventType(EventType::WINTER_EVENT))->isWinterEvent());
    }
}
