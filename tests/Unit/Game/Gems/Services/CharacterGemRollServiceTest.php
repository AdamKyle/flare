<?php

namespace Tests\Unit\Game\Gems\Services;

use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Gems\Services\CharacterGemRollService;
use App\Game\Gems\Values\CharacterGemModifierType;
use App\Game\Gems\Values\GemTierValue;
use Tests\TestCase;

class CharacterGemRollServiceTest extends TestCase
{
    public function test_tier_two_rolls_three_distinct_valid_modifiers(): void
    {
        $random = $this->createStub(RandomNumberGenerator::class);
        $random->method('numberBetween')->willReturnArgument(0);
        $service = new CharacterGemRollService($random, $this->createStub(ChanceCalculator::class));
        $rolls = $service->rollForTier(GemTierValue::TIER_TWO);
        $types = array_map(fn ($roll) => $roll->modifierType, $rolls);

        $this->assertCount(3, $rolls);
        $this->assertCount(3, array_unique($types, SORT_REGULAR));
    }

    public function test_tier_three_rolls_three_distinct_valid_modifiers(): void
    {
        $random = $this->createStub(RandomNumberGenerator::class);
        $random->method('numberBetween')->willReturnArgument(0);
        $service = new CharacterGemRollService($random, $this->createStub(ChanceCalculator::class));
        $rolls = $service->rollForTier(GemTierValue::TIER_THREE);
        $types = array_map(fn ($roll) => $roll->modifierType, $rolls);

        $this->assertCount(3, $rolls);
        $this->assertCount(3, array_unique($types, SORT_REGULAR));
    }

    public function test_tier_four_always_contains_exactly_one_atonement_and_three_modifiers(): void
    {
        $random = $this->createStub(RandomNumberGenerator::class);
        $random->method('numberBetween')->willReturnArgument(0);
        $service = new CharacterGemRollService($random, $this->createStub(ChanceCalculator::class));
        $rolls = $service->rollForTier(GemTierValue::TIER_FOUR);
        $types = array_map(fn ($roll) => $roll->modifierType, $rolls);

        $this->assertCount(3, $rolls);
        $this->assertSame(1, count(array_filter(
            $types,
            fn (CharacterGemModifierType $type): bool => in_array($type, CharacterGemModifierType::atonements(), true),
        )));
    }

    public function test_fire_atonement_can_only_pair_with_fire_penetration(): void
    {
        $random = $this->createStub(RandomNumberGenerator::class);
        $random->method('numberBetween')->willReturnArgument(0);
        $service = new CharacterGemRollService($random, $this->createStub(ChanceCalculator::class));
        $types = array_map(fn ($roll) => $roll->modifierType, $service->rollForTier(GemTierValue::TIER_FOUR));

        $this->assertContains(CharacterGemModifierType::FIRE_ATONEMENT, $types);
        $this->assertContains(CharacterGemModifierType::FIRE_PENETRATION, $types);
        $this->assertNotContains(CharacterGemModifierType::WATER_PENETRATION, $types);
        $this->assertNotContains(CharacterGemModifierType::ICE_PENETRATION, $types);
    }

    public function test_water_atonement_can_only_pair_with_water_penetration(): void
    {
        $random = $this->createStub(RandomNumberGenerator::class);
        $random->method('numberBetween')->willReturnCallback(fn (int $minimum, int $maximum): int => $maximum === 2 ? 1 : $minimum);
        $service = new CharacterGemRollService($random, $this->createStub(ChanceCalculator::class));
        $types = array_map(fn ($roll) => $roll->modifierType, $service->rollForTier(GemTierValue::TIER_FOUR));

        $this->assertContains(CharacterGemModifierType::WATER_ATONEMENT, $types);
        $this->assertContains(CharacterGemModifierType::WATER_PENETRATION, $types);
        $this->assertNotContains(CharacterGemModifierType::FIRE_PENETRATION, $types);
        $this->assertNotContains(CharacterGemModifierType::ICE_PENETRATION, $types);
    }

    public function test_ice_atonement_can_only_pair_with_ice_penetration(): void
    {
        $random = $this->createStub(RandomNumberGenerator::class);
        $random->method('numberBetween')->willReturnCallback(fn (int $minimum, int $maximum): int => $maximum === 2 ? 2 : $minimum);
        $service = new CharacterGemRollService($random, $this->createStub(ChanceCalculator::class));
        $types = array_map(fn ($roll) => $roll->modifierType, $service->rollForTier(GemTierValue::TIER_FOUR));

        $this->assertContains(CharacterGemModifierType::ICE_ATONEMENT, $types);
        $this->assertContains(CharacterGemModifierType::ICE_PENETRATION, $types);
        $this->assertNotContains(CharacterGemModifierType::FIRE_PENETRATION, $types);
        $this->assertNotContains(CharacterGemModifierType::WATER_PENETRATION, $types);
    }

    public function test_additional_level_occupies_one_additional_slot_without_replacing_atonement(): void
    {
        $chance = $this->createMock(ChanceCalculator::class);
        $chance->expects($this->once())->method('passesPercentage')->with(1)->willReturn(true);
        $random = $this->createStub(RandomNumberGenerator::class);
        $random->method('numberBetween')->willReturnArgument(0);
        $service = new CharacterGemRollService($random, $chance);
        $rolls = $service->rollForTier(GemTierValue::TIER_FOUR);
        $types = array_map(fn ($roll) => $roll->modifierType, $rolls);

        $this->assertCount(3, $rolls);
        $this->assertSame(1, count(array_filter(
            $types,
            fn (CharacterGemModifierType $type): bool => in_array($type, CharacterGemModifierType::atonements(), true),
        )));
        $this->assertSame(1, count(array_filter($types, fn ($type) => $type === CharacterGemModifierType::ADDITIONAL_LEVEL_GAIN)));
        $this->assertSame(1.0, $rolls[1]->amount);
    }

    public function test_two_non_rare_additional_tier_four_modifiers_are_distinct(): void
    {
        $chance = $this->createMock(ChanceCalculator::class);
        $chance->expects($this->once())->method('passesPercentage')->with(1)->willReturn(false);
        $random = $this->createStub(RandomNumberGenerator::class);
        $random->method('numberBetween')->willReturnArgument(0);
        $service = new CharacterGemRollService($random, $chance);
        $rolls = $service->rollForTier(GemTierValue::TIER_FOUR);

        $this->assertNotSame($rolls[1]->modifierType, $rolls[2]->modifierType);
        $this->assertNotContains(CharacterGemModifierType::ADDITIONAL_LEVEL_GAIN, [$rolls[1]->modifierType, $rolls[2]->modifierType]);
    }
}
