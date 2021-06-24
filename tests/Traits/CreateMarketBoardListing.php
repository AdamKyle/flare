<?php

namespace Tests\Traits;

use App\Flare\Models\MarketBoard;

trait CreateMarketBoardListing {

    public function createMarketBoardListing(array $options = []): MarketBoard {
        return MarketBoard::factory()->create($options);
    }
}
