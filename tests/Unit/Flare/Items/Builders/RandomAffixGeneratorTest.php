<?php

namespace Tests\Unit\Flare\Items\Builders;

use App\Flare\Items\Builders\AffixAttributeBuilder;
use App\Flare\Items\Builders\RandomAffixGenerator;
use App\Flare\Values\RandomAffixDetails;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class RandomAffixGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private ?RandomAffixGenerator $randomAffixGenerator;

    private ?CharacterFactory $characterFactory;

    public function setUp(): void
    {
        parent::setUp();

        $this->randomAffixGenerator = resolve(RandomAffixGenerator::class);

        $this->characterFactory = (new CharacterFactory)->createBaseCharacter();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->randomAffixGenerator = null;

        $this->characterFactory = null;
    }

    public function testShouldReturnTheSameAffixAndNotGenerateANewOne()
    {
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
