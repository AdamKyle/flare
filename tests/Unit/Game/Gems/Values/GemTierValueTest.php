<?php

namespace Tests\Unit\Game\Gems\Values;

use App\Game\Gems\Values\GemTierValue;
use Exception;
use Tests\TestCase;

class GemTierValueTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_throw_error_for_invalid_gem_tier_value()
    {
        $this->expectException(Exception::class);

        new GemTierValue(105);
    }

    public function test_return_null_for_max_tier_one_amount()
    {
        $value = (new GemTierValue(GemTierValue::TIER_FOUR))->maxTierOneAmount();

        $this->assertNull(
            $value
        );
    }

    public function test_return_null_for_max_tier_two_amount()
    {
        $value = (new GemTierValue(GemTierValue::TIER_FOUR))->maxTierTwoAmount();

        $this->assertNull(
            $value
        );
    }

    public function test_return_null_for_max_tier_three_amount()
    {
        $value = (new GemTierValue(GemTierValue::TIER_FOUR))->maxTierThreeAmount();

        $this->assertNull(
            $value
        );
    }

    public function test_return_null_for_max_tier_four_amount()
    {
        $value = (new GemTierValue(GemTierValue::TIER_ONE))->maxTierFourAmount();

        $this->assertNull(
            $value
        );
    }
}
