<?php

namespace Tests\Traits;

use App\Flare\Models\LocationTemplate;

trait CreateLocationTemplate
{
    public function createLocationTemplate(array $options = []): LocationTemplate
    {
        return LocationTemplate::factory()->create($options);
    }
}
