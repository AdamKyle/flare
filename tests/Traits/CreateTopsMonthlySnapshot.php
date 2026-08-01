<?php

namespace Tests\Traits;

use App\Flare\Models\TopsMonthlySnapshot;

trait CreateTopsMonthlySnapshot
{
    public function createTopsMonthlySnapshot(array $options = []): TopsMonthlySnapshot
    {
        return TopsMonthlySnapshot::factory()->create($options);
    }
}
