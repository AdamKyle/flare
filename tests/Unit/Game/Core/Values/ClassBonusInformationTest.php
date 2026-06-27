<?php

namespace Tests\Unit\Game\Core\Values;

use App\Flare\Values\ClassAttackValue;
use App\Game\Core\Values\View\ClassBonusInformation;
use Tests\TestCase;

class ClassBonusInformationTest extends TestCase
{
    public function test_buccaneers_class_bonus_details_contains_required_keys(): void
    {
        $details = (new ClassBonusInformation)->buildClassBonusDetailsForInfo('Buccaneer');

        $this->assertArrayHasKey('description', $details);
        $this->assertArrayHasKey('type', $details);
        $this->assertArrayHasKey('requires', $details);
        $this->assertArrayHasKey('base_chance', $details);
    }

    public function test_buccaneers_class_bonus_type_is_barrage(): void
    {
        $details = (new ClassBonusInformation)->buildClassBonusDetailsForInfo('Buccaneer');

        $this->assertEquals(ucfirst(ClassAttackValue::BUCCANEERS_BARRAGE), $details['type']);
    }

    public function test_buccaneers_class_bonus_requires_gun_and_shield(): void
    {
        $details = (new ClassBonusInformation)->buildClassBonusDetailsForInfo('Buccaneer');

        $this->assertStringContainsString('Gun and Shield', $details['requires']);
    }

    public function test_buccaneers_class_bonus_requires_includes_two_guns(): void
    {
        $details = (new ClassBonusInformation)->buildClassBonusDetailsForInfo('Buccaneer');

        $this->assertStringContainsString('Two Guns', $details['requires']);
    }

    public function test_buccaneers_class_bonus_description_is_not_empty(): void
    {
        $details = (new ClassBonusInformation)->buildClassBonusDetailsForInfo('Buccaneer');

        $this->assertNotEmpty($details['description']);
    }

    public function test_buccaneers_class_bonus_description_includes_gun_shield_damage_rates(): void
    {
        $details = (new ClassBonusInformation)->buildClassBonusDetailsForInfo('Buccaneer');

        $this->assertStringContainsString('25%', $details['description']);
        $this->assertStringContainsString('15%', $details['description']);
        $this->assertStringContainsString('5%', $details['description']);
    }

    public function test_buccaneers_class_bonus_description_includes_dual_gun_damage_rates(): void
    {
        $details = (new ClassBonusInformation)->buildClassBonusDetailsForInfo('Buccaneer');

        $this->assertStringContainsString('75%', $details['description']);
        $this->assertStringContainsString('55%', $details['description']);
        $this->assertStringContainsString('35%', $details['description']);
    }

    public function test_buccaneers_class_bonus_base_chance_is_set(): void
    {
        $details = (new ClassBonusInformation)->buildClassBonusDetailsForInfo('Buccaneer');

        $this->assertEquals(0.05, $details['base_chance']);
    }

    public function test_beastmaster_class_bonus_details_contains_required_keys(): void
    {
        $details = (new ClassBonusInformation)->buildClassBonusDetailsForInfo('Beastmaster');

        $this->assertArrayHasKey('description', $details);
        $this->assertArrayHasKey('type', $details);
        $this->assertArrayHasKey('requires', $details);
        $this->assertArrayHasKey('base_chance', $details);
    }

    public function test_beastmaster_class_bonus_requires_includes_bow(): void
    {
        $details = (new ClassBonusInformation)->buildClassBonusDetailsForInfo('Beastmaster');

        $this->assertStringContainsString('Bow', $details['requires']);
    }

    public function test_beastmaster_class_bonus_requires_includes_hammer(): void
    {
        $details = (new ClassBonusInformation)->buildClassBonusDetailsForInfo('Beastmaster');

        $this->assertStringContainsString('Hammer', $details['requires']);
    }

    public function test_beastmaster_class_bonus_description_mentions_devils_piercing_shot(): void
    {
        $details = (new ClassBonusInformation)->buildClassBonusDetailsForInfo('Beastmaster');

        $this->assertStringContainsString('Devils Piercing Shot', $details['description']);
    }

    public function test_beastmaster_class_bonus_description_mentions_beast_stomp(): void
    {
        $details = (new ClassBonusInformation)->buildClassBonusDetailsForInfo('Beastmaster');

        $this->assertStringContainsString('Beast Stomp', $details['description']);
    }

    public function test_beastmaster_class_bonus_description_mentions_bleed_percentages(): void
    {
        $details = (new ClassBonusInformation)->buildClassBonusDetailsForInfo('Beastmaster');

        $this->assertStringContainsString('17%', $details['description']);
        $this->assertStringContainsString('14%', $details['description']);
        $this->assertStringContainsString('8%', $details['description']);
        $this->assertStringContainsString('4%', $details['description']);
    }

    public function test_beastmaster_class_bonus_description_mentions_earth_crust(): void
    {
        $details = (new ClassBonusInformation)->buildClassBonusDetailsForInfo('Beastmaster');

        $this->assertStringContainsString('25%', $details['description']);
    }
}
