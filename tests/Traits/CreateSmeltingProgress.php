<?php

namespace Tests\Traits;

use App\Flare\Models\SmeltingProgress;

trait CreateSmeltingProgress
{
    public function createSmeltingProgress(array $options = []): SmeltingProgress
    {
        return SmeltingProgress::factory()->create($options);
    }
}
