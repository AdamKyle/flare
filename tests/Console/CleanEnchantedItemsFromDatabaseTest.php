<?php

namespace Tests\Console;

use App\Flare\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\MarketHistory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateMarketHistory;

class CleanEnchantedItemsFromDatabaseTest extends TestCase
{
    use RefreshDatabase, CreateItem, CreateItemAffix;

    public function testCleanEnchantedItems()
    {

        $this->createItem([
            'item_prefix_id' => $this->createItemAffix(['type' => 'prefix'])->id,
        ]);

        $this->createItem([
            'item_suffix_id' => $this->createItemAffix(['type' => 'suffix'])->id,
        ]);

        $this->assertEquals(0, $this->artisan('clean:enchanted-items'));

        $this->assertEquals(0, Item::count());
    }
}
