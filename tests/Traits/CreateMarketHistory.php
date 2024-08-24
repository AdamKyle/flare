<?php

namespace Tests\Traits;

use App\Flare\Models\MarketHistory;

trait CreateMarketHistory
{
    public function createMarketHistory(array $options = []): MarketHistory
    {
        return MarketHistory::factory()->create($options);
    }
}
