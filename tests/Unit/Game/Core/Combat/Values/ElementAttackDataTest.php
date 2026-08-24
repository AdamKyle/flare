<?php

namespace Tests\Unit\Game\Core\Combat\Values;

use App\Game\Core\Combat\Values\ElementAttackData;
use Exception;
use Tests\TestCase;

class ElementAttackDataTest extends TestCase
{
    public function test_get_highest_element_damage_with_scalar_values(): void
    {
        $elementAttackData = new ElementAttackData;

        $damage = $elementAttackData->getHighestElementDamage([
            'Fire' => 10.0,
            'Ice' => 5.0,
            'Water' => 2.0,
        ]);

        $this->assertSame(10.0, $damage);
    }

    public function test_get_highest_element_name_with_nested_array_values(): void
    {
        $elementAttackData = new ElementAttackData;

        $elementData = [
            'Fire' => ['Fire' => 10.0],
            'Ice' => ['Ice' => 5.0],
        ];

        $name = $elementAttackData->getHighestElementName($elementData, $elementAttackData->getHighestElementDamage($elementData));

        $this->assertSame('Fire', $name);
    }

    public function test_get_highest_element_damage_returns_zero_when_all_values_are_zero(): void
    {
        $elementAttackData = new ElementAttackData;

        $damage = $elementAttackData->getHighestElementDamage([
            'Fire' => 0.0,
            'Ice' => 0.0,
            'Water' => 0.0,
        ]);

        $this->assertSame(0.0, $damage);
    }

    public function test_get_highest_element_name_returns_unknown_when_element_data_is_empty(): void
    {
        $elementAttackData = new ElementAttackData;

        $name = $elementAttackData->getHighestElementName([], 0.0);

        $this->assertSame('UNKNOWN', $name);
    }

    public function test_is_half_damage_is_true_when_attacker_is_the_defenders_half_damage_opposite(): void
    {
        $elementAttackData = new ElementAttackData;

        $elementData = ['Fire' => 10.0, 'Ice' => 0.0, 'Water' => 0.0];

        $this->assertTrue($elementAttackData->isHalfDamage($elementData, 'Water'));
    }

    public function test_is_half_damage_is_false_when_attacker_is_not_the_defenders_half_damage_opposite(): void
    {
        $elementAttackData = new ElementAttackData;

        $elementData = ['Fire' => 10.0, 'Ice' => 0.0, 'Water' => 0.0];

        $this->assertFalse($elementAttackData->isHalfDamage($elementData, 'Ice'));
    }

    public function test_is_double_damage_is_true_when_attacker_is_the_defenders_double_damage_opposite(): void
    {
        $elementAttackData = new ElementAttackData;

        $elementData = ['Fire' => 10.0, 'Ice' => 0.0, 'Water' => 0.0];

        $this->assertTrue($elementAttackData->isDoubleDamage($elementData, 'Ice'));
    }

    public function test_is_double_damage_is_false_when_attacker_is_not_the_defenders_double_damage_opposite(): void
    {
        $elementAttackData = new ElementAttackData;

        $elementData = ['Fire' => 10.0, 'Ice' => 0.0, 'Water' => 0.0];

        $this->assertFalse($elementAttackData->isDoubleDamage($elementData, 'Water'));
    }

    public function test_is_half_damage_is_false_when_element_data_is_empty(): void
    {
        $elementAttackData = new ElementAttackData;

        $this->assertFalse($elementAttackData->isHalfDamage([], 'Water'));
    }

    public function test_is_double_damage_is_false_when_element_data_is_empty(): void
    {
        $elementAttackData = new ElementAttackData;

        $this->assertFalse($elementAttackData->isDoubleDamage([], 'Fire'));
    }

    public function test_is_half_damage_throws_when_the_highest_element_name_is_not_a_real_element(): void
    {
        $elementAttackData = new ElementAttackData;

        $this->expectException(Exception::class);

        $elementAttackData->isHalfDamage(['Bogus' => 10.0], 'Fire');
    }

    public function test_get_highest_elemental_value_returns_expected_shape(): void
    {
        $elementAttackData = new ElementAttackData;

        $result = $elementAttackData->getHighestElementalValue([
            'Fire' => 10.0,
            'Ice' => 0.0,
            'Water' => 0.0,
        ]);

        $this->assertSame(['' => 10.0], $result);
    }
}
