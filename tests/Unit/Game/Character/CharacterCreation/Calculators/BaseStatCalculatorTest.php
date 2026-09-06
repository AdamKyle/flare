<?php

namespace Tests\Unit\Game\Character\CharacterCreation\Calculators;

use App\Game\Character\CharacterCreation\Calculators\BaseStatCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateClass;

class BaseStatCalculatorTest extends TestCase
{
    use CreateClass, RefreshDatabase;

    public function test_positive_class_modifier_is_added_to_the_base_stat(): void
    {
        $gameClass = $this->createClass(['str_mod' => 4]);

        $calculator = (new BaseStatCalculator)->setClass($gameClass);

        $this->assertSame(14, $calculator->str());
    }

    public function test_negative_class_modifier_does_not_reduce_below_the_established_base(): void
    {
        $gameClass = $this->createClass(['str_mod' => -4]);

        $calculator = (new BaseStatCalculator)->setClass($gameClass);

        $this->assertSame(10, $calculator->str());
    }

    public function test_ac_uses_class_defense_modifier_only(): void
    {
        $gameClass = $this->createClass(['defense_mod' => 0.5]);

        $calculator = (new BaseStatCalculator)->setClass($gameClass);

        $this->assertSame(15, $calculator->ac());
    }
}
