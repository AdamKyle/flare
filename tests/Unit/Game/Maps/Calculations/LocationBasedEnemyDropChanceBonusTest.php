<?php

namespace Tests\Unit\Game\Maps\Calculations;

use Facades\App\Game\Maps\Calculations\LocationBasedEnemyDropChanceBonus;
use Tests\TestCase;

class LocationBasedEnemyDropChanceBonusTest extends TestCase
{
    public function test_returns_minimum_at_zero(): void
    {
        $result = LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent(0.0);

        $this->assertEquals(5.00, $result);
    }

    public function test_negative_values_clamp_to_minimum(): void
    {
        $result = LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent(-2.0);

        $this->assertEquals(5.00, $result);
    }

    public function test_mapping_at_point_one_zero(): void
    {
        $result = LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent(0.10);

        $this->assertEquals(5.91, $result);
    }

    public function test_mapping_at_point_one_five(): void
    {
        $result = LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent(0.15);

        $this->assertEquals(6.30, $result);
    }

    public function test_mapping_at_point_three_zero(): void
    {
        $result = LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent(0.30);

        $this->assertEquals(7.31, $result);
    }

    public function test_mapping_at_point_four_five(): void
    {
        $result = LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent(0.45);

        $this->assertEquals(8.10, $result);
    }

    public function test_mapping_at_one_point_zero(): void
    {
        $result = LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent(1.0);

        $this->assertEquals(10.00, $result);
    }

    public function test_monotonic_increase_across_rounded_samples(): void
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

    public function test_large_values_approach_but_do_not_reach_maximum(): void
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
