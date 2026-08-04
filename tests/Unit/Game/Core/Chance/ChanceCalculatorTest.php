<?php

namespace Tests\Unit\Game\Core\Chance;

use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class ChanceCalculatorTest extends TestCase
{
    public function test_zero_percentage_always_fails_without_a_roll(): void
    {
        $generator = Mockery::mock(RandomNumberGenerator::class);
        $generator->shouldNotReceive('numberBetween');

        $this->assertFalse((new ChanceCalculator($generator))->passesPercentage(0.0));
    }

    public function test_one_hundred_percentage_always_passes_without_a_roll(): void
    {
        $generator = Mockery::mock(RandomNumberGenerator::class);
        $generator->shouldNotReceive('numberBetween');

        $this->assertTrue((new ChanceCalculator($generator))->passesPercentage(100.0));
    }

    public function test_roll_on_basis_point_threshold_passes(): void
    {
        $generator = Mockery::mock(RandomNumberGenerator::class);
        $generator->shouldReceive('numberBetween')->once()->with(1, 10_000)->andReturn(2_055);

        $this->assertTrue((new ChanceCalculator($generator))->passesPercentage(20.55));
    }

    public function test_first_roll_above_basis_point_threshold_fails(): void
    {
        $generator = Mockery::mock(RandomNumberGenerator::class);
        $generator->shouldReceive('numberBetween')->once()->with(1, 10_000)->andReturn(2_056);

        $this->assertFalse((new ChanceCalculator($generator))->passesPercentage(20.55));
    }

    public function test_positive_modifier_is_added_as_percentage_points(): void
    {
        $generator = Mockery::mock(RandomNumberGenerator::class);
        $generator->shouldReceive('numberBetween')->once()->with(1, 10_000)->andReturn(2_500);

        $this->assertTrue((new ChanceCalculator($generator))->passesPercentage(20.0, 5.0));
    }

    public function test_negative_modifier_is_added_as_percentage_points(): void
    {
        $generator = Mockery::mock(RandomNumberGenerator::class);
        $generator->shouldReceive('numberBetween')->once()->with(1, 10_000)->andReturn(1_501);

        $this->assertFalse((new ChanceCalculator($generator))->passesPercentage(20.0, -5.0));
    }

    public function test_percentage_below_zero_clamps_without_a_roll(): void
    {
        $generator = Mockery::mock(RandomNumberGenerator::class);
        $generator->shouldNotReceive('numberBetween');

        $this->assertFalse((new ChanceCalculator($generator))->passesPercentage(-1.0));
    }

    public function test_percentage_above_one_hundred_clamps_without_a_roll(): void
    {
        $generator = Mockery::mock(RandomNumberGenerator::class);
        $generator->shouldNotReceive('numberBetween');

        $this->assertTrue((new ChanceCalculator($generator))->passesPercentage(101.0));
    }

    public function test_one_in_one_passes_without_a_roll(): void
    {
        $generator = Mockery::mock(RandomNumberGenerator::class);
        $generator->shouldNotReceive('numberBetween');

        $this->assertTrue((new ChanceCalculator($generator))->passesOneIn(1));
    }

    public function test_one_in_chance_passes_on_roll_one(): void
    {
        $generator = Mockery::mock(RandomNumberGenerator::class);
        $generator->shouldReceive('numberBetween')->once()->with(1, 20)->andReturn(1);

        $this->assertTrue((new ChanceCalculator($generator))->passesOneIn(20));
    }

    public function test_one_in_chance_fails_on_another_roll(): void
    {
        $generator = Mockery::mock(RandomNumberGenerator::class);
        $generator->shouldReceive('numberBetween')->once()->with(1, 20)->andReturn(2);

        $this->assertFalse((new ChanceCalculator($generator))->passesOneIn(20));
    }

    public function test_invalid_one_in_denominator_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The chance denominator must be at least one.');

        (new ChanceCalculator(Mockery::mock(RandomNumberGenerator::class)))->passesOneIn(0);
    }
}
