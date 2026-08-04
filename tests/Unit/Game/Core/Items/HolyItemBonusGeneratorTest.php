<?php

namespace Tests\Unit\Game\Core\Items;

use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Items\Services\HolyItemBonusGenerator;
use App\Game\Core\Items\Values\HolyItemLevel;
use PHPUnit\Framework\TestCase;

final class HolyItemBonusGeneratorTest extends TestCase
{
    public function test_level_one_uses_one_to_three_range(): void
    {
        $random = $this->createMock(RandomNumberGenerator::class);
        $random->expects($this->once())->method('numberBetween')->with(1, 3)->willReturn(3);

        $this->assertSame(3, (new HolyItemBonusGenerator($random))->getRandomStatIncrease(HolyItemLevel::LEVEL_ONE));
    }

    public function test_level_two_uses_one_to_five_range(): void
    {
        $random = $this->createMock(RandomNumberGenerator::class);
        $random->expects($this->once())->method('numberBetween')->with(1, 5)->willReturn(5);

        $this->assertSame(5, (new HolyItemBonusGenerator($random))->getRandomStatIncrease(HolyItemLevel::LEVEL_TWO));
    }

    public function test_level_three_uses_one_to_eight_range(): void
    {
        $random = $this->createMock(RandomNumberGenerator::class);
        $random->expects($this->once())->method('numberBetween')->with(1, 8)->willReturn(8);

        $this->assertSame(8, (new HolyItemBonusGenerator($random))->getRandomStatIncrease(HolyItemLevel::LEVEL_THREE));
    }

    public function test_level_four_uses_one_to_ten_range(): void
    {
        $random = $this->createMock(RandomNumberGenerator::class);
        $random->expects($this->once())->method('numberBetween')->with(1, 10)->willReturn(10);

        $this->assertSame(10, (new HolyItemBonusGenerator($random))->getRandomStatIncrease(HolyItemLevel::LEVEL_FOUR));
    }

    public function test_level_five_uses_one_to_fifteen_range(): void
    {
        $random = $this->createMock(RandomNumberGenerator::class);
        $random->expects($this->once())->method('numberBetween')->with(1, 15)->willReturn(15);

        $this->assertSame(15, (new HolyItemBonusGenerator($random))->getRandomStatIncrease(HolyItemLevel::LEVEL_FIVE));
    }

    public function test_devoidance_preserves_division_by_one_thousand(): void
    {
        $random = $this->createMock(RandomNumberGenerator::class);
        $random->expects($this->once())->method('numberBetween')->with(1, 15)->willReturn(15);

        $this->assertSame(0.015, (new HolyItemBonusGenerator($random))->getRandomDevoidanceIncrease(HolyItemLevel::LEVEL_FIVE));
    }
}
