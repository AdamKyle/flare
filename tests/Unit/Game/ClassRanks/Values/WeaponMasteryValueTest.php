<?php

namespace Tests\Unit\Game\ClassRanks\Values;

use App\Game\ClassRanks\Values\WeaponMasteryValue;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeaponMasteryValueTest extends TestCase {

    use RefreshDatabase;


    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    public function testInitializeWeaponMasteryValueWithInProperValue() {
        $this->expectException(Exception::class);

        new WeaponMasteryValue(13);
    }

    public function testFailToGetNumericValueForStringType() {
        $this->expectException(Exception::class);

        WeaponMasteryValue::getNumericValueForStringType('apples');
    }

    public function testFailToGetTypeForInvalidNumericValue() {
        $this->expectException(Exception::class);

        WeaponMasteryValue::getTypeForNumericalValue(500);
    }

    public function testGetNumericValueForWeaponType() {
        $this->assertEquals(WeaponMasteryValue::WEAPON, WeaponMasteryValue::getNumericValueForStringType('weapon'));
    }

    public function testGetNumericValueForHammerType() {
        $this->assertEquals(WeaponMasteryValue::HAMMER, WeaponMasteryValue::getNumericValueForStringType('hammer'));
    }

    public function testGetNumericValueForStaveType() {
        $this->assertEquals(WeaponMasteryValue::STAVE, WeaponMasteryValue::getNumericValueForStringType('stave'));
    }

    public function testGetNumericValueForBowType() {
        $this->assertEquals(WeaponMasteryValue::BOW, WeaponMasteryValue::getNumericValueForStringType('bow'));
    }

    public function testGetNumericValueForSpellDamageType() {
        $this->assertEquals(WeaponMasteryValue::DAMAGE_SPELL, WeaponMasteryValue::getNumericValueForStringType('spell-damage'));
    }

    public function testGetNumericValueForSpellHealingType() {
        $this->assertEquals(WeaponMasteryValue::HEALING_SPELL, WeaponMasteryValue::getNumericValueForStringType('spell-healing'));
    }

    public function testIsValidType() {
        $this->assertTrue(WeaponMasteryValue::isValidType('weapon'));
    }

    public function testGetAttributeName() {
        $this->assertEquals('Weapons', (new WeaponMasteryValue(WeaponMasteryValue::WEAPON))->getAttribute());
    }

    public function testIsStave() {
        $this->assertTrue((new WeaponMasteryValue(WeaponMasteryValue::STAVE))->isStaff());
    }

    public function testIsWeapon() {
        $this->assertTrue((new WeaponMasteryValue(WeaponMasteryValue::WEAPON))->isWeapon());
    }

    public function testIsBow() {
        $this->assertTrue((new WeaponMasteryValue(WeaponMasteryValue::BOW))->isBow());
    }

    public function testIsHammer() {
        $this->assertTrue((new WeaponMasteryValue(WeaponMasteryValue::HAMMER))->isHammer());
    }

    public function testIsSpellDamage() {
        $this->assertTrue((new WeaponMasteryValue(WeaponMasteryValue::DAMAGE_SPELL))->isDamageSpell());
    }

    public function testIsSpellHealing() {
        $this->assertTrue((new WeaponMasteryValue(WeaponMasteryValue::HEALING_SPELL))->isHealingSpell());
    }
}
