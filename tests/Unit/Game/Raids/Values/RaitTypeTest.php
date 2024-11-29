<?php

namespace Tests\Unit\Game\Raids\Values;

use App\Game\Raids\Values\RaidType;
use Exception;
use Tests\TestCase;

class RaitTypeTest extends TestCase
{
    public function testThrowsExceptionForInvalidType()
    {

        $this->expectException(Exception::class);

        new RaidType(45);
    }

    public function testIsPirateLordRaid()
    {
        $this->assertTrue(
            (new RaidType(RaidType::PIRATE_LORD))->isPirateLordRaid()
        );
    }

    public function testIsIceQueenRaid()
    {
        $this->assertTrue(
            (new RaidType(RaidType::ICE_QUEEN))->isIceQueenRaid()
        );
    }

    public function testIsJesterOfTime()
    {
        $this->assertTrue(
            (new RaidType(RaidType::JESTER_OF_TIME))->isJesterOfTime()
        );
    }

    public function testIsFrozenKing()
    {
        $this->assertTrue(
            (new RaidType(RaidType::FROZEN_KING))->isFrozenKing()
        );
    }

    public function testIsCorruptedBishop()
    {
        $this->assertTrue(
            (new RaidType(RaidType::CORRUPTED_BISHOP))->isCorruptedBishop()
        );
    }
}
