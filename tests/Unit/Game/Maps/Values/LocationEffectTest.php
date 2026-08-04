<?php

namespace Tests\Unit\Game\Maps\Values;

use App\Game\Maps\Values\LocationEffect;
use Tests\TestCase;

class LocationEffectTest extends TestCase
{
    public function test_increase_name_labels_describe_points_and_percentage_for_each_tier(): void
    {
        $this->assertSame('250pts and 2% towards resistances and skills.', LocationEffect::getIncreaseName(0));
        $this->assertSame('1,000pts and 8% towards resistances and skills. ', LocationEffect::getIncreaseName(2));
        $this->assertSame('50,000pts and 60% towards resistances and skills.', LocationEffect::getIncreaseName(6));
    }

    public function test_increase_by_amount_returns_the_configured_point_value_for_each_tier(): void
    {
        $this->assertSame(250, LocationEffect::getIncreaseByAmount(0));
        $this->assertSame(3000, LocationEffect::getIncreaseByAmount(4));
        $this->assertSame(50000, LocationEffect::getIncreaseByAmount(6));
    }

    public function test_gold_drop_rate_increase_percentage_is_applied_for_each_tier(): void
    {
        $this->assertSame(0.02, LocationEffect::INCREASE_STATS_BY_TWO_HUNDRED_FIFTY->fetchDropRate());
        $this->assertSame(0.05, LocationEffect::INCREASE_STATS_BY_FIVE_HUNDRED->fetchDropRate());
        $this->assertSame(0.08, LocationEffect::INCREASE_STATS_BY_ONE_THOUSAND->fetchDropRate());
        $this->assertSame(0.10, LocationEffect::INCREASE_STATS_BY_TWO_THOUSAND->fetchDropRate());
        $this->assertSame(0.14, LocationEffect::INCREASE_STATS_BY_THREE_THOUSAND->fetchDropRate());
        $this->assertSame(0.30, LocationEffect::INCREASE_STATS_BY_TEN_THOUSAND->fetchDropRate());
        $this->assertSame(0.60, LocationEffect::INCREASE_STATS_BY_FIFTY_THOUSAND->fetchDropRate());
    }
}
