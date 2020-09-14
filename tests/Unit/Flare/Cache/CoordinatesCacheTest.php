<?php

namespace Tests\Unit\Flare\Cache;

use App\Flare\Cache\CoordinatesCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoordinatesCacheTest extends TestCase
{
    use RefreshDatabase;

    public function testCacheisCreated()
    {
        $coordinatesCache = resolve(CoordinatesCache::class)->getFromCache();

        $this->assertTrue(!empty($coordinatesCache['x']));
        $this->assertTrue(!empty($coordinatesCache['y']));
    }
}
