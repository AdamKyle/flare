<?php

namespace Tests\Unit\Game\Maps\Calculations;

use Facades\App\Game\Maps\Calculations\LocationBasedEnemyDropChanceBonus;
use Tests\TestCase;

class LocationBasedEnemyDropChanceBonusTest extends TestCase
{
    public function testReturnsMinimumAtZero(): void
    {
        $result = LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent(0.0);

        $this->assertEquals(5.00, $result);
    }

    public function testNegativeValuesClampToMinimum(): void
    {
        $result = LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent(-2.0);

        $this->assertEquals(5.00, $result);
    }

    public function testMappingAtPointOneZero(): void
    {
        $result = LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent(0.10);

        $this->assertEquals(5.91, $result);
    }

    public function testMappingAtPointOneFive(): void
    {
        $result = LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent(0.15);

        $this->assertEquals(6.30, $result);
    }

    public function testMappingAtPointThreeZero(): void
    {
        $result = LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent(0.30);

        $this->assertEquals(7.31, $result);
    }

    public function testMappingAtPointFourFive(): void
    {
        $result = LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent(0.45);

        $this->assertEquals(8.10, $result);
    }

    public function testMappingAtOnePointZero(): void
    {
        $result = LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent(1.0);

        $this->assertEquals(10.00, $result);
    }

    public function testMonotonicIncreaseAcrossRoundedSamples(): void
    {
        $samples = [0.0, 0.10, 0.15, 0.30, 0.45, 1.0, 2.0];

        $previous = null;

        foreach ($samples as $value) {
            $current = LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent($value);

            if ($previous !== null) {
                $this->assertGreaterThan($previous, $current);
            }

            $previous = $current;
        }
    }

    public function testLargeValuesApproachButDoNotReachMaximum(): void
    {
        $ten = LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent(10.0);
        $hundred = LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent(100.0);
        $thousand = LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent(1000.0);

        $this->assertEquals(14.09, $ten);
        $this->assertEquals(14.90, $hundred);
        $this->assertEquals(14.99, $thousand);

        $this->assertLessThan(15.00, $ten);
        $this->assertLessThan(15.00, $hundred);
        $this->assertLessThan(15.00, $thousand);

        $this->assertGreaterThan($ten, $hundred);
        $this->assertGreaterThan($hundred, $thousand);
    }
}
