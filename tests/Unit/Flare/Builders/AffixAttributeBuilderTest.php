<?php

namespace Tests\Unit\Flare\Builders;

use App\Flare\Builders\AffixAttributeBuilder;
use Tests\TestCase;

class AffixAttributeBuilderTest extends TestCase {

    private ?AffixAttributeBuilder $affixAttributeBuilder;

    public function setUp(): void {
        parent::setUp();

        $this->affixAttributeBuilder = resolve(AffixAttributeBuilder::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->affixAttributeBuilder = null;
    }

    public function testBuildAffixAttributesWithOutSkillInfo() {
        $attributes = $this->affixAttributeBuilder
            ->setPercentageRange([0.10, 0.90])
            ->setDamageRange([1, 1000])
            ->buildAttributes('prefix', 20, true);

        $this->assertArrayNotHasKey('skill_name', $attributes);
    }
}
