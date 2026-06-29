<?php

namespace Tests\Traits;

use App\Flare\Models\BatchCrafting;

trait CreateBatchCrafting
{
    public function createBatchCrafting(array $details): BatchCrafting
    {
        return BatchCrafting::factory()->create($details);
    }
}
