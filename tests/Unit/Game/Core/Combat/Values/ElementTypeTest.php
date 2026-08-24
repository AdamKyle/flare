<?php

namespace Tests\Unit\Game\Core\Combat\Values;

use App\Game\Core\Combat\Values\ElementType;
use Tests\TestCase;

class ElementTypeTest extends TestCase
{
    public function test_fire_half_damage_opposite_is_water(): void
    {
        $this->assertSame(ElementType::WATER, ElementType::FIRE->halfDamageOpposite());
    }

    public function test_ice_half_damage_opposite_is_fire(): void
    {
        $this->assertSame(ElementType::FIRE, ElementType::ICE->halfDamageOpposite());
    }

    public function test_water_half_damage_opposite_is_ice(): void
    {
        $this->assertSame(ElementType::ICE, ElementType::WATER->halfDamageOpposite());
    }

    public function test_water_double_damage_opposite_is_fire(): void
    {
        $this->assertSame(ElementType::FIRE, ElementType::WATER->doubleDamageOpposite());
    }

    public function test_fire_double_damage_opposite_is_ice(): void
    {
        $this->assertSame(ElementType::ICE, ElementType::FIRE->doubleDamageOpposite());
    }

    public function test_ice_double_damage_opposite_is_water(): void
    {
        $this->assertSame(ElementType::WATER, ElementType::ICE->doubleDamageOpposite());
    }
}
