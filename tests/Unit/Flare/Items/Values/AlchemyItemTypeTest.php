<?php

namespace Tests\Unit\Flare\Items\Values;

use App\Flare\Items\Values\AlchemyItemType;
use PHPUnit\Framework\TestCase;

class AlchemyItemTypeTest extends TestCase
{
    public function test_increases_stats(): void
    {
        $this->assertTrue(AlchemyItemType::INCREASE_STATS->increasesStats());

        foreach (AlchemyItemType::cases() as $case) {
            if ($case !== AlchemyItemType::INCREASE_STATS) {
                $this->assertFalse($case->increasesStats());
            }
        }
    }

    public function test_increases_damage(): void
    {
        $this->assertTrue(AlchemyItemType::INCREASE_DAMAGE->increasesDamage());

        foreach (AlchemyItemType::cases() as $case) {
            if ($case !== AlchemyItemType::INCREASE_DAMAGE) {
                $this->assertFalse($case->increasesDamage());
            }
        }
    }

    public function test_increases_armour(): void
    {
        $this->assertTrue(AlchemyItemType::INCREASE_ARMOUR->increasesArmour());

        foreach (AlchemyItemType::cases() as $case) {
            if ($case !== AlchemyItemType::INCREASE_ARMOUR) {
                $this->assertFalse($case->increasesArmour());
            }
        }
    }

    public function test_increases_healing(): void
    {
        $this->assertTrue(AlchemyItemType::INCREASE_HEALING->increasesHealing());

        foreach (AlchemyItemType::cases() as $case) {
            if ($case !== AlchemyItemType::INCREASE_HEALING) {
                $this->assertFalse($case->increasesHealing());
            }
        }
    }

    public function test_increases_skill_type(): void
    {
        $this->assertTrue(AlchemyItemType::INCREASE_SKILL_TYPE->increasesSkillType());

        foreach (AlchemyItemType::cases() as $case) {
            if ($case !== AlchemyItemType::INCREASE_SKILL_TYPE) {
                $this->assertFalse($case->increasesSkillType());
            }
        }
    }

    public function test_damages_kingdoms(): void
    {
        $this->assertTrue(AlchemyItemType::DAMAGES_KINGDOMS->damagesKingdoms());

        foreach (AlchemyItemType::cases() as $case) {
            if ($case !== AlchemyItemType::DAMAGES_KINGDOMS) {
                $this->assertFalse($case->damagesKingdoms());
            }
        }
    }

    public function test_is_holy_oil_type(): void
    {
        $this->assertTrue(AlchemyItemType::HOLY_OILS->isHolyOilType());

        foreach (AlchemyItemType::cases() as $case) {
            if ($case !== AlchemyItemType::HOLY_OILS) {
                $this->assertFalse($case->isHolyOilType());
            }
        }
    }
}
