<?php

namespace Tests\Unit\Flare\Items\Values;

use App\Flare\Items\Values\ArmourType;
use PHPUnit\Framework\TestCase;

class ArmourTypeTest extends TestCase
{
    public function test_get_armour_positions(): void
    {
        $positions = ArmourType::getArmourPositions();

        $this->assertIsArray($positions);

        $expectedKeys = array_map(fn (ArmourType $t) => $t->value, ArmourType::cases());
        $this->assertEqualsCanonicalizing($expectedKeys, array_keys($positions));

        $this->assertEquals(['left-hand', 'right-hand'], $positions[ArmourType::SHIELD->value]);
        $this->assertEquals(['body'], $positions[ArmourType::BODY->value]);
        $this->assertEquals(['leggings'], $positions[ArmourType::LEGGINGS->value]);
        $this->assertEquals(['sleeves'], $positions[ArmourType::SLEEVES->value]);
        $this->assertEquals(['gloves'], $positions[ArmourType::GLOVES->value]);
        $this->assertEquals(['feet'], $positions[ArmourType::FEET->value]);
        $this->assertEquals(['helmet'], $positions[ArmourType::HELMET->value]);
    }

    public function test_all_types(): void
    {
        $all = ArmourType::allTypes();

        $this->assertIsArray($all);
        $this->assertEqualsCanonicalizing(
            array_map(fn (ArmourType $t) => $t->value, ArmourType::cases()),
            $all
        );
    }
}
