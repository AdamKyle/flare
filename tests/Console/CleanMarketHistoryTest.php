<?php

namespace Tests\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\MarketHistory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMarketHistory;

class CleanMarketHistoryTest extends TestCase
{
    use RefreshDatabase, CreateMarketHistory, CreateItem;

    public function testCleanMarketHistory()
    {

        $item = $this->createItem();

        $this->createMarketHistory([
            'item_id'    => $item->id,
            'sold_for'   => 10000,
            'created_at' => now()->subDays(120)
        ]);   

        $this->assertEquals(0, $this->artisan('clean:market-history'));

        $this->assertEquals(0, MarketHistory::all()->count());
    }
}
