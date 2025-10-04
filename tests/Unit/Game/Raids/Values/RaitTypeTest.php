<?php

namespace Tests\Unit\Game\Raids\Values;

use App\Game\Raids\Values\RaidType;
use Exception;
use Tests\TestCase;

class RaitTypeTest extends TestCase
{
    public function test_throws_exception_for_invalid_type()
    {

        $this->expectException(Exception::class);

        new RaidType(45);
    }

    public function test_is_pirate_lord_raid()
    {
        $this->assertTrue(
            (new RaidType(RaidType::PIRATE_LORD))->isPirateLordRaid()
        );
    }

    public function test_is_ice_queen_raid()
    {
        $this->assertTrue(
            (new RaidType(RaidType::ICE_QUEEN))->isIceQueenRaid()
        );
    }

    public function test_is_jester_of_time()
    {
        $this->assertTrue(
            (new RaidType(RaidType::JESTER_OF_TIME))->isJesterOfTime()
        );
    }

    public function test_is_frozen_king()
    {
        $this->assertTrue(
            (new RaidType(RaidType::FROZEN_KING))->isFrozenKing()
        );
    }

    public function test_is_corrupted_bishop()
    {
        $this->assertTrue(
            (new RaidType(RaidType::CORRUPTED_BISHOP))->isCorruptedBishop()
        );
    }
}
