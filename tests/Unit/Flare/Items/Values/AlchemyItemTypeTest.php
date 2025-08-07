<?php

namespace Tests\Unit\Flare\Items\Values;

use App\Flare\Items\Values\AlchemyItemType;
use PHPUnit\Framework\TestCase;

class AlchemyItemTypeTest extends TestCase
{
    public function testIncreasesStats(): void
    {
        $this->assertTrue(AlchemyItemType::INCREASE_STATS->increasesStats());

        foreach (AlchemyItemType::cases() as $case) {
            if ($case !== AlchemyItemType::INCREASE_STATS) {
                $this->assertFalse($case->increasesStats());
            }
        }
    }

    public function testIncreasesDamage(): void
    {
        $this->assertTrue(AlchemyItemType::INCREASE_DAMAGE->increasesDamage());

        foreach (AlchemyItemType::cases() as $case) {
            if ($case !== AlchemyItemType::INCREASE_DAMAGE) {
                $this->assertFalse($case->increasesDamage());
            }
        }
    }

    public function testIncreasesArmour(): void
    {
        $this->assertTrue(AlchemyItemType::INCREASE_ARMOUR->increasesArmour());

        foreach (AlchemyItemType::cases() as $case) {
            if ($case !== AlchemyItemType::INCREASE_ARMOUR) {
                $this->assertFalse($case->increasesArmour());
            }
        }
    }

    public function testIncreasesHealing(): void
    {
        $this->assertTrue(AlchemyItemType::INCREASE_HEALING->increasesHealing());

        foreach (AlchemyItemType::cases() as $case) {
            if ($case !== AlchemyItemType::INCREASE_HEALING) {
                $this->assertFalse($case->increasesHealing());
            }
        }
    }

    public function testIncreasesSkillType(): void
    {
        $this->assertTrue(AlchemyItemType::INCREASE_SKILL_TYPE->increasesSkillType());

        foreach (AlchemyItemType::cases() as $case) {
            if ($case !== AlchemyItemType::INCREASE_SKILL_TYPE) {
                $this->assertFalse($case->increasesSkillType());
            }
        }
    }

    public function testDamagesKingdoms(): void
    {
        $this->assertTrue(AlchemyItemType::DAMAGES_KINGDOMS->damagesKingdoms());

        foreach (AlchemyItemType::cases() as $case) {
            if ($case !== AlchemyItemType::DAMAGES_KINGDOMS) {
                $this->assertFalse($case->damagesKingdoms());
            }
        }
    }

    public function testIsHolyOilType(): void
    {
        $this->assertTrue(AlchemyItemType::HOLY_OILS->isHolyOilType());

        foreach (AlchemyItemType::cases() as $case) {
            if ($case !== AlchemyItemType::HOLY_OILS) {
                $this->assertFalse($case->isHolyOilType());
            }
        }
    }
}
