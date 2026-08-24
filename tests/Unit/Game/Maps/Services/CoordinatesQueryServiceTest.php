<?php

namespace Tests\Unit\Game\Maps\Services;

use App\Game\Maps\Cache\CoordinatesCache;
use App\Game\Maps\Services\CoordinatesQueryService;
use App\Game\Maps\Values\Coordinates;
use Mockery;
use Tests\TestCase;

class CoordinatesQueryServiceTest extends TestCase
{
    public function test_get_returns_a_coordinates_value_built_from_the_cache(): void
    {
        $coordinatesCache = Mockery::mock(CoordinatesCache::class);
        $coordinatesCache->shouldReceive('getFromCache')->once()->andReturn([
            'x' => [0, 16, 32],
            'y' => [16, 32, 48],
        ]);

        $service = new CoordinatesQueryService($coordinatesCache);

        $coordinates = $service->get();

        $this->assertInstanceOf(Coordinates::class, $coordinates);
        $this->assertSame([0, 16, 32], $coordinates->x);
        $this->assertSame([16, 32, 48], $coordinates->y);
    }
}
