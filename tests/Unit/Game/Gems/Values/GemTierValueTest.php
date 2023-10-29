<?php

namespace Tests\Unit\Game\Gems\Services;

use App\Game\Gems\Values\GemTierValue;
use Exception;
use Tests\TestCase;

class GemTierValueTest extends TestCase {

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    public function testThrowErrorForInvalidGemTierValue() {
        $this->expectException(Exception::class);

        new GemTierValue(105);
    }

    public function testReturnNullForMaxTierOneAmount() {
        $value = (new GemTierValue(GemTierValue::TIER_FOUR))->maxTierOneAmount();

        $this->assertNull(
            $value
        );
    }

    public function testReturnNullForMaxTierTwoAmount() {
        $value = (new GemTierValue(GemTierValue::TIER_FOUR))->maxTierTwoAmount();

        $this->assertNull(
            $value
        );
    }

    public function testReturnNullForMaxTierThreeAmount() {
        $value = (new GemTierValue(GemTierValue::TIER_FOUR))->maxTierThreeAmount();

        $this->assertNull(
            $value
        );
    }

    public function testReturnNullForMaxTierFourAmount() {
        $value = (new GemTierValue(GemTierValue::TIER_ONE))->maxTierFourAmount();

        $this->assertNull(
            $value
        );
    }
}
