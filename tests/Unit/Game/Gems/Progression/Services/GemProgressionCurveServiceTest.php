<?php

namespace Tests\Unit\Game\Gems\Progression\Services;

use App\Game\Gems\Progression\Services\GemProgressionCurveService;
use Tests\TestCase;

class GemProgressionCurveServiceTest extends TestCase
{
    private GemProgressionCurveService $gemProgressionCurveService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gemProgressionCurveService = new GemProgressionCurveService;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_global_level_one_requires_one_thousand_xp()
    {
        $this->assertSame(1000, $this->gemProgressionCurveService->xpRequiredForGlobalLevel(1));
    }

    public function test_global_level_ninety_nine_requires_one_million_xp()
    {
        $this->assertSame(1_000_000, $this->gemProgressionCurveService->xpRequiredForGlobalLevel(99));
    }

    public function test_global_level_one_hundred_has_no_next_level_requirement()
    {
        $this->assertNull($this->gemProgressionCurveService->xpRequiredForGlobalLevel(100));
    }

    public function test_personal_level_one_matches_global_curve()
    {
        $this->assertSame(1000, $this->gemProgressionCurveService->xpRequiredForPersonalLevel(1));
    }

    public function test_personal_level_ninety_nine_matches_global_curve()
    {
        $this->assertSame(1_000_000, $this->gemProgressionCurveService->xpRequiredForPersonalLevel(99));
    }

    public function test_personal_level_one_hundred_requires_one_million_xp()
    {
        $this->assertSame(1_000_000, $this->gemProgressionCurveService->xpRequiredForPersonalLevel(100));
    }

    public function test_personal_level_five_hundred_requires_ten_million_xp()
    {
        $this->assertSame(10_000_000, $this->gemProgressionCurveService->xpRequiredForPersonalLevel(500));
    }

    public function test_personal_level_nine_hundred_ninety_nine_requires_one_billion_xp()
    {
        $this->assertSame(1_000_000_000, $this->gemProgressionCurveService->xpRequiredForPersonalLevel(999));
    }

    public function test_personal_level_one_thousand_has_no_next_level_requirement()
    {
        $this->assertNull($this->gemProgressionCurveService->xpRequiredForPersonalLevel(1000));
    }

    public function test_global_curve_increases_monotonically_within_its_segment()
    {
        $lowerLevelCost = $this->gemProgressionCurveService->xpRequiredForGlobalLevel(10);
        $higherLevelCost = $this->gemProgressionCurveService->xpRequiredForGlobalLevel(50);

        $this->assertGreaterThan($lowerLevelCost, $higherLevelCost);
    }

    public function test_personal_mid_band_curve_increases_monotonically_within_its_segment()
    {
        $lowerLevelCost = $this->gemProgressionCurveService->xpRequiredForPersonalLevel(150);
        $higherLevelCost = $this->gemProgressionCurveService->xpRequiredForPersonalLevel(400);

        $this->assertGreaterThan($lowerLevelCost, $higherLevelCost);
    }

    public function test_personal_high_band_curve_increases_monotonically_within_its_segment()
    {
        $lowerLevelCost = $this->gemProgressionCurveService->xpRequiredForPersonalLevel(600);
        $higherLevelCost = $this->gemProgressionCurveService->xpRequiredForPersonalLevel(900);

        $this->assertGreaterThan($lowerLevelCost, $higherLevelCost);
    }

    public function test_all_xp_requirements_are_rounded_to_the_nearest_hundred()
    {
        $xpRequired = $this->gemProgressionCurveService->xpRequiredForPersonalLevel(250);

        $this->assertSame(0, $xpRequired % 100);
    }
}
