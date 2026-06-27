<?php

namespace Tests\Unit\Game\Gems\Builders;

use App\Flare\Models\Gem;
use App\Game\Gems\Builders\GemBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Traits\CreateGem;

class GemBuilderTest extends TestCase
{
    use CreateGem, RefreshDatabase;

    public function test_create_a_gem()
    {
        $gemBuilder = resolve(GemBuilder::class);

        $createdGem = $gemBuilder->buildGem(1);

        $gem = Gem::find($createdGem->id);

        $this->assertNotNull($gem);

        $this->assertEquals(1, $gem->tier);
        $this->assertSame(Gem::DOMAIN_CHARACTER, $gem->domain);
    }

    public function test_find_existing_gem()
    {
        $gem = $this->createGem();

        $this->instance(
            GemBuilder::class,
            Mockery::mock(GemBuilder::class, function (MockInterface $mock) use ($gem) {
                $mock->makePartial()->shouldAllowMockingProtectedMethods()->shouldReceive('buildDataForGem')->once()->andReturn($gem->getAttributes());
            })
        );

        $gemBuilder = resolve(GemBuilder::class);

        $foundGem = $gemBuilder->buildGem(1);

        $this->assertEquals($gem->id, $foundGem->id);
    }

    public function test_generated_map_gem_is_not_reused_as_character_gem(): void
    {
        $characterGemData = $this->createGem()->only([
            'name',
            'tier',
            'primary_atonement_type',
            'secondary_atonement_type',
            'tertiary_atonement_type',
            'primary_atonement_amount',
            'secondary_atonement_amount',
            'tertiary_atonement_amount',
        ]);
        Gem::query()->delete();
        $mapGem = Gem::create(array_merge($characterGemData, [
            'domain' => Gem::DOMAIN_MAP,
        ]));

        $this->instance(
            GemBuilder::class,
            Mockery::mock(GemBuilder::class, function (MockInterface $mock) use ($characterGemData) {
                $mock->makePartial()
                    ->shouldAllowMockingProtectedMethods()
                    ->shouldReceive('buildDataForGem')
                    ->once()
                    ->andReturn(array_merge($characterGemData, ['domain' => Gem::DOMAIN_CHARACTER]));
            })
        );

        $characterGem = resolve(GemBuilder::class)->buildGem(1);

        $this->assertNotSame($mapGem->id, $characterGem->id);
        $this->assertSame(Gem::DOMAIN_CHARACTER, $characterGem->domain);
    }
}
