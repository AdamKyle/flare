<?php

namespace Tests\Unit\Game\Gems\Progression\Services;

use App\Game\Gems\Progression\Services\GemProgressionEffectService;
use Tests\TestCase;

class GemProgressionEffectServiceTest extends TestCase
{
    private GemProgressionEffectService $gemProgressionEffectService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gemProgressionEffectService = new GemProgressionEffectService;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_level_one_adds_zero_progression_effect()
    {
        $totalBonus = $this->gemProgressionEffectService->globalPositiveBonus(0.05, 1)
            + $this->gemProgressionEffectService->personalBaseBonus(0.05, 1);

        $this->assertSame(0.0, $totalBonus);
    }

    public function test_global_level_one_hundred_adds_exactly_one_original_positive_rolled_amount()
    {
        $this->assertEqualsWithDelta(0.05, $this->gemProgressionEffectService->globalPositiveBonus(0.05, 100), 0.0000001);
    }

    public function test_personal_level_one_hundred_adds_exactly_one_original_positive_rolled_amount()
    {
        $this->assertEqualsWithDelta(0.05, $this->gemProgressionEffectService->personalBaseBonus(0.05, 100), 0.0000001);
    }

    public function test_rolled_five_percent_with_global_and_personal_max_base_resolves_to_fifteen_percent()
    {
        $effectiveValue = $this->gemProgressionEffectService->effectivePositiveValue(0.05, 100, 100);

        $this->assertEqualsWithDelta(0.15, $effectiveValue, 0.0000001);
    }

    public function test_personal_level_two_hundred_positive_band_extra_is_three_percent()
    {
        $this->assertEqualsWithDelta(0.03, $this->gemProgressionEffectService->personalPositiveBandBonus(0.05, 200), 0.0000001);
    }

    public function test_negative_progression_at_personal_level_two_hundred_is_three_percent()
    {
        $this->assertEqualsWithDelta(0.03, $this->gemProgressionEffectService->personalNegativeBonus(200), 0.0000001);
    }

    public function test_negative_progression_at_personal_level_three_hundred_is_cumulative_five_percent()
    {
        $this->assertEqualsWithDelta(0.05, $this->gemProgressionEffectService->personalNegativeBonus(300), 0.0000001);
    }

    public function test_negative_progression_at_personal_level_five_hundred_is_cumulative_seven_percent()
    {
        $this->assertEqualsWithDelta(0.07, $this->gemProgressionEffectService->personalNegativeBonus(500), 0.0000001);
    }

    public function test_negative_progression_at_personal_level_seven_hundred_is_cumulative_nine_percent()
    {
        $this->assertEqualsWithDelta(0.09, $this->gemProgressionEffectService->personalNegativeBonus(700), 0.0000001);
    }

    public function test_negative_progression_at_personal_level_one_thousand_is_cumulative_eleven_percent()
    {
        $this->assertEqualsWithDelta(0.11, $this->gemProgressionEffectService->personalNegativeBonus(1000), 0.0000001);
    }

    public function test_unique_mythic_bonus_at_personal_level_three_hundred_is_two_percent()
    {
        $this->assertEqualsWithDelta(0.02, $this->gemProgressionEffectService->personalUniqueMythicBonus(300), 0.0000001);
    }

    public function test_unique_mythic_bonus_at_personal_level_five_hundred_is_ten_percent()
    {
        $this->assertEqualsWithDelta(0.10, $this->gemProgressionEffectService->personalUniqueMythicBonus(500), 0.0000001);
    }

    public function test_cosmic_bonus_at_personal_level_five_hundred_is_one_percent()
    {
        $this->assertEqualsWithDelta(0.01, $this->gemProgressionEffectService->personalCosmicBonus(500), 0.0000001);
    }

    public function test_cosmic_bonus_at_personal_level_seven_hundred_is_eight_percent()
    {
        $this->assertEqualsWithDelta(0.08, $this->gemProgressionEffectService->personalCosmicBonus(700), 0.0000001);
    }

    public function test_enhanced_equipment_chance_is_zero_below_level_seven_hundred()
    {
        $this->assertSame(0.0, $this->gemProgressionEffectService->enhancedEquipmentChance(699));
    }

    public function test_enhanced_equipment_chance_is_one_percent_at_level_seven_hundred()
    {
        $this->assertEqualsWithDelta(0.01, $this->gemProgressionEffectService->enhancedEquipmentChance(700), 0.0000001);
    }

    public function test_no_bonus_is_invented_for_a_rolled_value_that_is_not_positive()
    {
        $this->assertSame(0.0, $this->gemProgressionEffectService->globalPositiveBonus(0.0, 100));
    }

    public function test_no_negative_bonus_is_invented_when_the_resolved_negative_value_is_zero()
    {
        $this->assertSame(0.0, $this->gemProgressionEffectService->effectiveNegativeValue(0.0, 1000));
    }

    public function test_effective_negative_value_adds_personal_negative_progression_on_top_of_resolved_value()
    {
        $effectiveValue = $this->gemProgressionEffectService->effectiveNegativeValue(0.20, 200);

        $this->assertEqualsWithDelta(0.23, $effectiveValue, 0.0000001);
    }

    public function test_scroll_drop_is_not_eligible_below_personal_level_one_hundred()
    {
        $this->assertFalse($this->gemProgressionEffectService->isScrollDropEligible(99));
    }

    public function test_scroll_drop_is_eligible_at_personal_level_one_hundred()
    {
        $this->assertTrue($this->gemProgressionEffectService->isScrollDropEligible(100));
    }
}
