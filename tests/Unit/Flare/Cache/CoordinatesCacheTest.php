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
        $coordinatesCache = resolve(CoordinatesCache::class);
        
        $coordinates = $coordinatesCache->getFromCache();

        // Fetch again - this time from the cache.
        $coordinates = $coordinatesCache->getFromCache();

        $this->assertTrue(!empty($coordinates['x']));
        $this->assertTrue(!empty($coordinates['y']));


    }
}
