<?php

namespace Tests\Unit\Game\Maps\Cache;

use App\Game\Maps\Cache\CoordinatesCache;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CoordinatesCacheTest extends TestCase
{
    public function test_first_invocation_builds_the_complete_x_and_y_coordinate_values(): void
    {
        $coordinatesCache = new CoordinatesCache;

        $coordinates = $coordinatesCache->getFromCache();

        $this->assertSame($coordinatesCache->buildXCoordinates(), $coordinates['x']);
        $this->assertSame($coordinatesCache->buildYCoordinates(), $coordinates['y']);
    }

    public function test_repeated_invocation_uses_the_same_cache_contract(): void
    {
        Cache::put('coordinates', ['x' => [1, 2, 3], 'y' => [4, 5, 6]]);

        $coordinatesCache = new CoordinatesCache;

        $this->assertSame(['x' => [1, 2, 3], 'y' => [4, 5, 6]], $coordinatesCache->getFromCache());
    }

    public function test_x_coordinates_begin_at_the_existing_factual_starting_point(): void
    {
        $coordinatesCache = new CoordinatesCache;

        $this->assertSame(0, $coordinatesCache->buildXCoordinates()[0]);
    }

    public function test_y_coordinates_begin_at_the_existing_factual_starting_point(): void
    {
        $coordinatesCache = new CoordinatesCache;

        $this->assertSame(16, $coordinatesCache->buildYCoordinates()[0]);
    }

    public function test_coordinate_values_advance_by_exactly_16(): void
    {
        $coordinatesCache = new CoordinatesCache;

        $xCoordinates = $coordinatesCache->buildXCoordinates();

        $this->assertSame(16, $xCoordinates[1] - $xCoordinates[0]);
    }

    public function test_coordinate_values_stop_according_to_the_existing_maximum(): void
    {
        $coordinatesCache = new CoordinatesCache;

        $xCoordinates = $coordinatesCache->buildXCoordinates();
        $yCoordinates = $coordinatesCache->buildYCoordinates();

        $this->assertSame(2480, end($xCoordinates));
        $this->assertSame(2496, end($yCoordinates));
    }
}
