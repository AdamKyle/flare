<?php

namespace Database\Factories\Flare\Models;

use App\Flare\Models\CapitalCityResourceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class CapitalCityResourceRequestFactory extends Factory
{
    protected $model = CapitalCityResourceRequest::class;

    public function definition(): array
    {
        return [
            'kingdom_requesting_id' => 1,
            'request_from_kingdom_id' => 1,
            'resources' => [],
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ];
    }
}
