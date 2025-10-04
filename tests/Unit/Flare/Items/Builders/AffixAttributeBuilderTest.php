<?php

namespace Tests\Unit\Flare\Items\Builders;

use App\Flare\Items\Builders\AffixAttributeBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffixAttributeBuilderTest extends TestCase
{
    use RefreshDatabase;

    private ?AffixAttributeBuilder $affixAttributeBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->affixAttributeBuilder = resolve(AffixAttributeBuilder::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->affixAttributeBuilder = null;
    }

    public function test_build_affix_attributes_with_out_skill_info()
    {
        $attributes = $this->affixAttributeBuilder
            ->setPercentageRange([0.10, 0.90])
            ->setDamageRange([1, 1000])
            ->buildAttributes('prefix', 20, true);

        $this->assertArrayNotHasKey('skill_name', $attributes);
    }
}
