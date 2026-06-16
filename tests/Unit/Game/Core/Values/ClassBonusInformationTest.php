<?php

namespace Tests\Unit\Game\Core\Values;

use App\Flare\Values\ClassAttackValue;
use App\Game\Core\Values\View\ClassBonusInformation;
use Tests\TestCase;

class ClassBonusInformationTest extends TestCase
{
    public function testBuccaneersClassBonusDetailsContainsRequiredKeys(): void
    {
        $details = (new ClassBonusInformation)->buildClassBonusDetailsForInfo('Buccaneer');

        $this->assertArrayHasKey('description', $details);
        $this->assertArrayHasKey('type', $details);
        $this->assertArrayHasKey('requires', $details);
        $this->assertArrayHasKey('base_chance', $details);
    }

    public function testBuccaneersClassBonusTypeIsBarrage(): void
    {
        $details = (new ClassBonusInformation)->buildClassBonusDetailsForInfo('Buccaneer');

        $this->assertEquals(ucfirst(ClassAttackValue::BUCCANEERS_BARRAGE), $details['type']);
    }

    public function testBuccaneersClassBonusRequiresGunAndShield(): void
    {
        $details = (new ClassBonusInformation)->buildClassBonusDetailsForInfo('Buccaneer');

        $this->assertStringContainsString('Gun and Shield', $details['requires']);
    }

    public function testBuccaneersClassBonusRequiresIncludesTwoGuns(): void
    {
        $details = (new ClassBonusInformation)->buildClassBonusDetailsForInfo('Buccaneer');

        $this->assertStringContainsString('Two Guns', $details['requires']);
    }

    public function testBuccaneersClassBonusDescriptionIsNotEmpty(): void
    {
        $details = (new ClassBonusInformation)->buildClassBonusDetailsForInfo('Buccaneer');

        $this->assertNotEmpty($details['description']);
    }

    public function testBuccaneersClassBonusDescriptionIncludesGunShieldDamageRates(): void
    {
        $details = (new ClassBonusInformation)->buildClassBonusDetailsForInfo('Buccaneer');

        $this->assertStringContainsString('25%', $details['description']);
        $this->assertStringContainsString('15%', $details['description']);
        $this->assertStringContainsString('5%', $details['description']);
    }

    public function testBuccaneersClassBonusDescriptionIncludesDualGunDamageRates(): void
    {
        $details = (new ClassBonusInformation)->buildClassBonusDetailsForInfo('Buccaneer');

        $this->assertStringContainsString('75%', $details['description']);
        $this->assertStringContainsString('55%', $details['description']);
        $this->assertStringContainsString('35%', $details['description']);
    }

    public function testBuccaneersClassBonusBaseChanceIsSet(): void
    {
        $details = (new ClassBonusInformation)->buildClassBonusDetailsForInfo('Buccaneer');

        $this->assertEquals(0.05, $details['base_chance']);
    }

    public function testBeastmasterClassBonusDetailsContainsRequiredKeys(): void
    {
        $details = (new ClassBonusInformation)->buildClassBonusDetailsForInfo('Beastmaster');

        $this->assertArrayHasKey('description', $details);
        $this->assertArrayHasKey('type', $details);
        $this->assertArrayHasKey('requires', $details);
        $this->assertArrayHasKey('base_chance', $details);
    }

    public function testBeastmasterClassBonusRequiresIncludesBow(): void
    {
        $details = (new ClassBonusInformation)->buildClassBonusDetailsForInfo('Beastmaster');

        $this->assertStringContainsString('Bow', $details['requires']);
    }

    public function testBeastmasterClassBonusRequiresIncludesHammer(): void
    {
        $details = (new ClassBonusInformation)->buildClassBonusDetailsForInfo('Beastmaster');

        $this->assertStringContainsString('Hammer', $details['requires']);
    }

    public function testBeastmasterClassBonusDescriptionMentionsDevilsPiercingShot(): void
    {
        $details = (new ClassBonusInformation)->buildClassBonusDetailsForInfo('Beastmaster');

        $this->assertStringContainsString('Devils Piercing Shot', $details['description']);
    }

    public function testBeastmasterClassBonusDescriptionMentionsBeastStomp(): void
    {
        $details = (new ClassBonusInformation)->buildClassBonusDetailsForInfo('Beastmaster');

        $this->assertStringContainsString('Beast Stomp', $details['description']);
    }

    public function testBeastmasterClassBonusDescriptionMentionsBleedPercentages(): void
    {
        $details = (new ClassBonusInformation)->buildClassBonusDetailsForInfo('Beastmaster');

        $this->assertStringContainsString('17%', $details['description']);
        $this->assertStringContainsString('14%', $details['description']);
        $this->assertStringContainsString('8%', $details['description']);
        $this->assertStringContainsString('4%', $details['description']);
    }

    public function testBeastmasterClassBonusDescriptionMentionsEarthCrust(): void
    {
        $details = (new ClassBonusInformation)->buildClassBonusDetailsForInfo('Beastmaster');

        $this->assertStringContainsString('25%', $details['description']);
    }
}
