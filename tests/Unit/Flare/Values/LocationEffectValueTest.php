<?php

namespace Tests\Unit\Flare\Values;

use App\Flare\Values\LocationEffectValue;
use Tests\TestCase;


class LocationEffectValueTest extends TestCase {

    public function testThrowError() {

        $this->expectException(\Exception::class);

        new LocationEffectValue(88);
    }


    public function testIncreasesByOneHundredThousand() {
        $this->assertTrue((new LocationEffectValue(LocationEffectValue::INCREASE_STATS_BY_HUNDRED_THOUSAND))->increasesByOneHundredThousand());
    }

    public function testIncreasesByOneMillion() {
        $this->assertTrue((new LocationEffectValue(LocationEffectValue::INCREASE_STATS_BY_ONE_MILLION))->increaseByOneMillion());
    }

    public function testIncreasesByTenMillion() {
        $this->assertTrue((new LocationEffectValue(LocationEffectValue::INCREASE_STATS_BY_TEN_MILLION))->increaseByTenMillion());
    }

    public function testIncreasesByHundredMillion() {
        $this->assertTrue((new LocationEffectValue(LocationEffectValue::INCREASE_STATS_BY_HUNDRED_MILLION))->increaseByHundredMillion());
    }

    public function testIncreasesByOneBillion() {
        $this->assertTrue((new LocationEffectValue(LocationEffectValue::INCREASE_STATS_BY_ONE_BILLION))->increaseByOneBillion());
    }

    public function testFetchDropRateOfTwoPercent() {
        $this->assertEquals(.02, (new LocationEffectValue(LocationEffectValue::INCREASE_STATS_BY_HUNDRED_THOUSAND))->fetchDropRate());
    }

    public function testFetchDropRateOfFivePercent() {
        $this->assertEquals(.05, (new LocationEffectValue(LocationEffectValue::INCREASE_STATS_BY_ONE_MILLION))->fetchDropRate());
    }

    public function testFetchDropRateOfEightPercent() {
        $this->assertEquals(.08, (new LocationEffectValue(LocationEffectValue::INCREASE_STATS_BY_TEN_MILLION))->fetchDropRate());
    }

    public function testFetchDropRateOfTenPercent() {
        $this->assertEquals(.10, (new LocationEffectValue(LocationEffectValue::INCREASE_STATS_BY_HUNDRED_MILLION))->fetchDropRate());
    }

    public function testFetchDropRateOfFourteenPercent() {
        $this->assertEquals(.14, (new LocationEffectValue(LocationEffectValue::INCREASE_STATS_BY_ONE_BILLION))->fetchDropRate());
    }

    public function testFetchPercentageIncreaseOfFivePercent() {
        $this->assertEquals(0.05, LocationEffectValue::fetchPercentageIncrease(LocationEffectValue::INCREASE_STATS_BY_HUNDRED_THOUSAND));
    }

    public function testFetchPercentageIncreaseOfTenPercent() {
        $this->assertEquals(0.10, LocationEffectValue::fetchPercentageIncrease(LocationEffectValue::INCREASE_STATS_BY_ONE_MILLION));
    }

    public function testFetchPercentageIncreaseOfTwentyFivePercent() {
        $this->assertEquals(0.25, LocationEffectValue::fetchPercentageIncrease(LocationEffectValue::INCREASE_STATS_BY_TEN_MILLION));
    }

    public function testFetchPercentageIncreaseOfFiftyPercent() {
        $this->assertEquals(0.50, LocationEffectValue::fetchPercentageIncrease(LocationEffectValue::INCREASE_STATS_BY_HUNDRED_MILLION));
    }

    public function testFetchPercentageIncreaseOfSeventyPercent() {
        $this->assertEquals(0.70, LocationEffectValue::fetchPercentageIncrease(LocationEffectValue::INCREASE_STATS_BY_ONE_BILLION));
    }

    public function testFetchPercentageIncreaseOfZeroPercent() {
        $this->assertEquals(0.0, LocationEffectValue::fetchPercentageIncrease(67));
    }
}
