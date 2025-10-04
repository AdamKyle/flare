<?php

namespace Tests\Unit\Game\ClassRanks\Values;

use App\Game\ClassRanks\Values\WeaponMasteryValue;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeaponMasteryValueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_initialize_weapon_mastery_value_with_in_proper_value()
    {
        $this->expectException(Exception::class);

        new WeaponMasteryValue(13);
    }

    public function test_fail_to_get_numeric_value_for_string_type()
    {
        $this->expectException(Exception::class);

        WeaponMasteryValue::getNumericValueForStringType('apples');
    }

    public function test_fail_to_get_type_for_invalid_numeric_value()
    {
        $this->expectException(Exception::class);

        WeaponMasteryValue::getTypeForNumericalValue(500);
    }

    public function test_get_numeric_value_for_weapon_type()
    {
        $this->assertEquals(WeaponMasteryValue::WEAPON, WeaponMasteryValue::getNumericValueForStringType('weapon'));
    }

    public function test_get_numeric_value_for_hammer_type()
    {
        $this->assertEquals(WeaponMasteryValue::HAMMER, WeaponMasteryValue::getNumericValueForStringType('hammer'));
    }

    public function test_get_numeric_value_for_stave_type()
    {
        $this->assertEquals(WeaponMasteryValue::STAVE, WeaponMasteryValue::getNumericValueForStringType('stave'));
    }

    public function test_get_numeric_value_for_bow_type()
    {
        $this->assertEquals(WeaponMasteryValue::BOW, WeaponMasteryValue::getNumericValueForStringType('bow'));
    }

    public function test_get_numeric_value_for_spell_damage_type()
    {
        $this->assertEquals(WeaponMasteryValue::DAMAGE_SPELL, WeaponMasteryValue::getNumericValueForStringType('spell-damage'));
    }

    public function test_get_numeric_value_for_spell_healing_type()
    {
        $this->assertEquals(WeaponMasteryValue::HEALING_SPELL, WeaponMasteryValue::getNumericValueForStringType('spell-healing'));
    }

    public function test_get_numeric_value_for_gun_type()
    {
        $this->assertEquals(WeaponMasteryValue::GUN, WeaponMasteryValue::getNumericValueForStringType('gun'));
    }

    public function test_get_numeric_value_for_fan_type()
    {
        $this->assertEquals(WeaponMasteryValue::FAN, WeaponMasteryValue::getNumericValueForStringType('fan'));
    }

    public function test_get_numeric_value_for_mace_type()
    {
        $this->assertEquals(WeaponMasteryValue::MACE, WeaponMasteryValue::getNumericValueForStringType('mace'));
    }

    public function test_get_numeric_value_for_scratch_awl_type()
    {
        $this->assertEquals(WeaponMasteryValue::SCRATCH_AWL, WeaponMasteryValue::getNumericValueForStringType('scratch-awl'));
    }

    public function test_is_valid_type()
    {
        $this->assertTrue(WeaponMasteryValue::isValidType('weapon'));
    }

    public function test_get_attribute_name()
    {
        $this->assertEquals('Weapons', (new WeaponMasteryValue(WeaponMasteryValue::WEAPON))->getAttribute());
    }

    public function test_is_stave()
    {
        $this->assertTrue((new WeaponMasteryValue(WeaponMasteryValue::STAVE))->isStaff());
    }

    public function test_is_weapon()
    {
        $this->assertTrue((new WeaponMasteryValue(WeaponMasteryValue::WEAPON))->isWeapon());
    }

    public function test_is_bow()
    {
        $this->assertTrue((new WeaponMasteryValue(WeaponMasteryValue::BOW))->isBow());
    }

    public function test_is_hammer()
    {
        $this->assertTrue((new WeaponMasteryValue(WeaponMasteryValue::HAMMER))->isHammer());
    }

    public function test_is_spell_damage()
    {
        $this->assertTrue((new WeaponMasteryValue(WeaponMasteryValue::DAMAGE_SPELL))->isDamageSpell());
    }

    public function test_is_spell_healing()
    {
        $this->assertTrue((new WeaponMasteryValue(WeaponMasteryValue::HEALING_SPELL))->isHealingSpell());
    }

    public function test_is_gun()
    {
        $this->assertTrue((new WeaponMasteryValue(WeaponMasteryValue::GUN))->isGun());
    }

    public function test_is_fan()
    {
        $this->assertTrue((new WeaponMasteryValue(WeaponMasteryValue::FAN))->isFan());
    }

    public function test_is_mace()
    {
        $this->assertTrue((new WeaponMasteryValue(WeaponMasteryValue::MACE))->isMace());
    }

    public function testis_scratch_awl()
    {
        $this->assertTrue((new WeaponMasteryValue(WeaponMasteryValue::SCRATCH_AWL))->isScratchAwl());
    }
}
