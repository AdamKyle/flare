<?php

namespace Tests\Unit\Flare\Builders;

use Mockery;
use Tests\TestCase;
use App\Flare\Values\RandomAffixDetails;
use Tests\Setup\Character\CharacterFactory;
use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Builders\AffixAttributeBuilder;

class RandomAffixGeneratorTest extends TestCase {

    private ?RandomAffixGenerator $randomAffixGenerator;

    private ?CharacterFactory $characterFactory;

    public function setUp(): void {
        parent::setUp();

        $this->randomAffixGenerator = resolve(RandomAffixGenerator::class);

        $this->characterFactory = (new CharacterFactory)->createBaseCharacter();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->randomAffixGenerator = null;

        $this->characterFactory = null;
    }

    public function testShouldReturnTheSameAffixAndNotGenerateANewOne() {
        $character = $this->characterFactory->getCharacter();

        $itemAffix = $this->randomAffixGenerator->setCharacter($character)
            ->setPaidAmount(RandomAffixDetails::LEGENDARY)
            ->generateAffix('prefix');

        $mock = Mockery::mock(RandomAffixGenerator::class, [resolve(AffixAttributeBuilder::class)])->shouldAllowMockingProtectedMethods()->makePartial();

        $mock->shouldReceive('fetchMatchingAffix')->andReturn($itemAffix);

        $this->app->instance(RandomAffixGenerator::class, $mock);

        $randomAffixGenerator = $this->app->make(RandomAffixGenerator::class);

        $generatedItemAffix = $randomAffixGenerator->setCharacter($character)
            ->setPaidAmount(RandomAffixDetails::LEGENDARY)
            ->generateAffix('prefix');

        $this->assertEquals($itemAffix->id, $generatedItemAffix->id);
    }
}
