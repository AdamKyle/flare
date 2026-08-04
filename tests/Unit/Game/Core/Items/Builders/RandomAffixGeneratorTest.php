<?php

namespace Tests\Unit\Game\Core\Items\Builders;

use App\Game\Core\Items\Builders\AffixAttributeBuilder;
use App\Game\Core\Items\Builders\RandomAffixGenerator;
use App\Game\Core\Items\Values\RandomAffixTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class RandomAffixGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private ?RandomAffixGenerator $randomAffixGenerator;

    private ?CharacterFactory $characterFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->randomAffixGenerator = resolve(RandomAffixGenerator::class);

        $this->characterFactory = (new CharacterFactory)->createBaseCharacter();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->randomAffixGenerator = null;

        $this->characterFactory = null;
    }

    public function test_should_return_the_same_affix_and_not_generate_a_new_one()
    {
        $character = $this->characterFactory->getCharacter();

        $itemAffix = $this->randomAffixGenerator->setCharacter($character)
            ->setPaidAmount(RandomAffixTier::LEGENDARY->value)
            ->generateAffix('prefix');

        $mock = Mockery::mock(RandomAffixGenerator::class, [resolve(AffixAttributeBuilder::class)])->shouldAllowMockingProtectedMethods()->makePartial();

        $mock->shouldReceive('fetchMatchingAffix')->andReturn($itemAffix);

        $this->app->instance(RandomAffixGenerator::class, $mock);

        $randomAffixGenerator = $this->app->make(RandomAffixGenerator::class);

        $generatedItemAffix = $randomAffixGenerator->setCharacter($character)
            ->setPaidAmount(RandomAffixTier::LEGENDARY->value)
            ->generateAffix('prefix');

        $this->assertEquals($itemAffix->id, $generatedItemAffix->id);
    }
}
