<?php

namespace Tests\Unit\Game\Maps\Calculations;

use Facades\App\Game\Maps\Calculations\LocationBasedEnemyDropChanceBonus;
use Tests\TestCase;

class LocationBasedEnemyDropChanceBonusTest extends TestCase
{
    public function test_returns_minimum_at_zero(): void
    {
        $result = LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent(0.0);

        $this->assertEquals(0.02, $result);
    }

    public function test_negative_values_clamp_to_minimum(): void
    {
        $result = LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent(-2.0);

        $this->assertEquals(0.02, $result);
    }

    public function test_mapping_at_point_one_zero(): void
    {
        $result = LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent(0.10);

        $this->assertEquals(0.03, $result);
    }

    public function test_mapping_at_point_one_five(): void
    {
        $result = LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent(0.15);

        $this->assertEquals(0.04, $result);
    }

    public function test_mapping_at_point_three_zero(): void
    {
        $result = LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent(0.30);

        $this->assertEquals(0.05, $result);
    }

    public function test_mapping_at_point_four_five(): void
    {
        $result = LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent(0.45);

        $this->assertEquals(0.06, $result);
    }

    public function test_mapping_at_one_point_zero(): void
    {
        $result = LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent(1.0);

        $this->assertEquals(0.09, $result);
    }

    public function test_monotonic_non_decreasing_across_rounded_samples(): void
    {
        $samples = [0.0, 0.10, 0.15, 0.30, 0.45, 1.0, 2.0];

        $previous = null;

        foreach ($samples as $value) {
            $current = LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent($value);

            if ($previous !== null) {
                $this->assertGreaterThanOrEqual($previous, $current);
            }

            $previous = $current;
        }

        $first = LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent($samples[0]);
        $last = LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent(end($samples));

        $this->assertGreaterThan($first, $last);
    }

    public function test_large_values_approach_but_do_not_exceed_maximum(): void
    {
        $ten = LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent(10.0);
        $hundred = LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent(100.0);
        $thousand = LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent(1000.0);

        $this->assertEquals(0.14, $ten);
        $this->assertEquals(0.15, $hundred);
        $this->assertEquals(0.15, $thousand);

        $this->assertLessThanOrEqual(0.15, $ten);
        $this->assertLessThanOrEqual(0.15, $hundred);
        $this->assertLessThanOrEqual(0.15, $thousand);

        $this->assertGreaterThanOrEqual($ten, $hundred);
        $this->assertGreaterThanOrEqual($hundred, $thousand);
    }
}
