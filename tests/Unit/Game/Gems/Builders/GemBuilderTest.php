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
}
