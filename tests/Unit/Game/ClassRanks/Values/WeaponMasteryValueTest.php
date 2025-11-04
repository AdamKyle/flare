<?php

namespace Tests\Unit\Game\ClassRanks\Values;

use App\Game\ClassRanks\Values\WeaponMasteryValue;
use Exception;
use Tests\TestCase;

class WeaponMasteryValueTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_initialize_weapon_mastery_value_with_improper_value(): void
    {
        $this->expectException(Exception::class);

        new WeaponMasteryValue(9999);
    }

    public function test_fail_to_get_numeric_value_for_string_type(): void
    {
        $this->expectException(Exception::class);

        WeaponMasteryValue::getNumericValueForStringType('apples');
    }

    public function test_fail_to_get_type_for_invalid_numeric_value(): void
    {
        $this->expectException(Exception::class);

        WeaponMasteryValue::getTypeForNumericalValue(500);
    }

    public function test_get_numeric_value_for_all_string_types(): void
    {
        $this->assertSame(WeaponMasteryValue::WEAPON, WeaponMasteryValue::getNumericValueForStringType('weapon'));
        $this->assertSame(WeaponMasteryValue::HAMMER, WeaponMasteryValue::getNumericValueForStringType('hammer'));
        $this->assertSame(WeaponMasteryValue::STAVE, WeaponMasteryValue::getNumericValueForStringType('stave'));
        $this->assertSame(WeaponMasteryValue::BOW, WeaponMasteryValue::getNumericValueForStringType('bow'));
        $this->assertSame(WeaponMasteryValue::GUN, WeaponMasteryValue::getNumericValueForStringType('gun'));
        $this->assertSame(WeaponMasteryValue::FAN, WeaponMasteryValue::getNumericValueForStringType('fan'));
        $this->assertSame(WeaponMasteryValue::MACE, WeaponMasteryValue::getNumericValueForStringType('mace'));
        $this->assertSame(WeaponMasteryValue::SCRATCH_AWL, WeaponMasteryValue::getNumericValueForStringType('scratch-awl'));
        $this->assertSame(WeaponMasteryValue::CLAWN, WeaponMasteryValue::getNumericValueForStringType('claw'));
        $this->assertSame(WeaponMasteryValue::CENSOR, WeaponMasteryValue::getNumericValueForStringType('censor'));
        $this->assertSame(WeaponMasteryValue::WAND, WeaponMasteryValue::getNumericValueForStringType('wand'));
        $this->assertSame(WeaponMasteryValue::SWORD, WeaponMasteryValue::getNumericValueForStringType('sword'));
        $this->assertSame(WeaponMasteryValue::DAMAGE_SPELL, WeaponMasteryValue::getNumericValueForStringType('spell-damage'));
        $this->assertSame(WeaponMasteryValue::HEALING_SPELL, WeaponMasteryValue::getNumericValueForStringType('spell-healing'));
        $this->assertSame(WeaponMasteryValue::WEAPON, WeaponMasteryValue::getNumericValueForStringType('WEAPON'));
    }

    public function test_get_type_for_all_numeric_values(): void
    {
        $this->assertSame('weapon', WeaponMasteryValue::getTypeForNumericalValue(WeaponMasteryValue::WEAPON));
        $this->assertSame('bow', WeaponMasteryValue::getTypeForNumericalValue(WeaponMasteryValue::BOW));
        $this->assertSame('hammer', WeaponMasteryValue::getTypeForNumericalValue(WeaponMasteryValue::HAMMER));
        $this->assertSame('stave', WeaponMasteryValue::getTypeForNumericalValue(WeaponMasteryValue::STAVE));
        $this->assertSame('spell-damage', WeaponMasteryValue::getTypeForNumericalValue(WeaponMasteryValue::DAMAGE_SPELL));
        $this->assertSame('spell-healing', WeaponMasteryValue::getTypeForNumericalValue(WeaponMasteryValue::HEALING_SPELL));
        $this->assertSame('gun', WeaponMasteryValue::getTypeForNumericalValue(WeaponMasteryValue::GUN));
        $this->assertSame('fan', WeaponMasteryValue::getTypeForNumericalValue(WeaponMasteryValue::FAN));
        $this->assertSame('mace', WeaponMasteryValue::getTypeForNumericalValue(WeaponMasteryValue::MACE));
        $this->assertSame('scratch-awl', WeaponMasteryValue::getTypeForNumericalValue(WeaponMasteryValue::SCRATCH_AWL));
        $this->assertSame('claw', WeaponMasteryValue::getTypeForNumericalValue(WeaponMasteryValue::CLAWN));
        $this->assertSame('censor', WeaponMasteryValue::getTypeForNumericalValue(WeaponMasteryValue::CENSOR));
        $this->assertSame('wand', WeaponMasteryValue::getTypeForNumericalValue(WeaponMasteryValue::WAND));
        $this->assertSame('sword', WeaponMasteryValue::getTypeForNumericalValue(WeaponMasteryValue::SWORD));
    }

    public function test_is_valid_type(): void
    {
        $this->assertTrue(WeaponMasteryValue::isValidType('weapon'));
        $this->assertTrue(WeaponMasteryValue::isValidType('stave'));
        $this->assertTrue(WeaponMasteryValue::isValidType('bow'));
        $this->assertTrue(WeaponMasteryValue::isValidType('hammer'));
        $this->assertTrue(WeaponMasteryValue::isValidType('gun'));
        $this->assertTrue(WeaponMasteryValue::isValidType('fan'));
        $this->assertTrue(WeaponMasteryValue::isValidType('mace'));
        $this->assertTrue(WeaponMasteryValue::isValidType('scratch-awl'));
        $this->assertTrue(WeaponMasteryValue::isValidType('claw'));
        $this->assertTrue(WeaponMasteryValue::isValidType('censor'));
        $this->assertTrue(WeaponMasteryValue::isValidType('wand'));
        $this->assertTrue(WeaponMasteryValue::isValidType('sword'));
        $this->assertTrue(WeaponMasteryValue::isValidType('spell-damage'));
        $this->assertTrue(WeaponMasteryValue::isValidType('spell-healing'));
        $this->assertFalse(WeaponMasteryValue::isValidType('applesauce'));
    }

    public function test_get_attribute_and_get_name(): void
    {
        $this->assertSame('Weapons', (new WeaponMasteryValue(WeaponMasteryValue::WEAPON))->getAttribute());
        $this->assertSame('Bows', (new WeaponMasteryValue(WeaponMasteryValue::BOW))->getName());
        $this->assertSame('Guns', (new WeaponMasteryValue(WeaponMasteryValue::GUN))->getName());
        $this->assertSame('Fans', (new WeaponMasteryValue(WeaponMasteryValue::FAN))->getName());
        $this->assertSame('Maces', (new WeaponMasteryValue(WeaponMasteryValue::MACE))->getName());
        $this->assertSame('scratch-awl', (new WeaponMasteryValue(WeaponMasteryValue::SCRATCH_AWL))->getName());
        $this->assertSame('Hammers', (new WeaponMasteryValue(WeaponMasteryValue::HAMMER))->getName());
        $this->assertSame('Staves', (new WeaponMasteryValue(WeaponMasteryValue::STAVE))->getName());
        $this->assertSame('Damage Spells', (new WeaponMasteryValue(WeaponMasteryValue::DAMAGE_SPELL))->getName());
        $this->assertSame('Healing Spells', (new WeaponMasteryValue(WeaponMasteryValue::HEALING_SPELL))->getName());
    }

    public function test_boolean_checkers(): void
    {
        $this->assertTrue((new WeaponMasteryValue(WeaponMasteryValue::STAVE))->isStaff());
        $this->assertTrue((new WeaponMasteryValue(WeaponMasteryValue::WEAPON))->isWeapon());
        $this->assertTrue((new WeaponMasteryValue(WeaponMasteryValue::BOW))->isBow());
        $this->assertTrue((new WeaponMasteryValue(WeaponMasteryValue::HAMMER))->isHammer());
        $this->assertTrue((new WeaponMasteryValue(WeaponMasteryValue::GUN))->isGun());
        $this->assertTrue((new WeaponMasteryValue(WeaponMasteryValue::FAN))->isFan());
        $this->assertTrue((new WeaponMasteryValue(WeaponMasteryValue::MACE))->isMace());
        $this->assertTrue((new WeaponMasteryValue(WeaponMasteryValue::SCRATCH_AWL))->isScratchAwl());
        $this->assertTrue((new WeaponMasteryValue(WeaponMasteryValue::CLAWN))->isClaw());
        $this->assertTrue((new WeaponMasteryValue(WeaponMasteryValue::CENSOR))->isCensor());
        $this->assertTrue((new WeaponMasteryValue(WeaponMasteryValue::WAND))->isWand());
        $this->assertTrue((new WeaponMasteryValue(WeaponMasteryValue::SWORD))->isSword());
        $this->assertTrue((new WeaponMasteryValue(WeaponMasteryValue::DAMAGE_SPELL))->isDamageSpell());
        $this->assertTrue((new WeaponMasteryValue(WeaponMasteryValue::HEALING_SPELL))->isHealingSpell());
    }

    public function test_get_types(): void
    {
        $types = WeaponMasteryValue::getTypes();

        $this->assertIsArray($types);
        $this->assertArrayHasKey(WeaponMasteryValue::WEAPON, $types);
        $this->assertArrayHasKey(WeaponMasteryValue::SWORD, $types);
    }
}
