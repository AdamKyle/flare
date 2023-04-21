<?php

namespace Tests\Console;

use App\Flare\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\MarketHistory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateMarketHistory;

class AddHolyStacksToItemsTest extends TestCase
{
    use RefreshDatabase, CreateItem, CreateItemAffix;

    public function testAddHolyStacks()
    {

        $highLevelItem = $this->createItem([
            'skill_level_trivial' => 400
        ]);

        $lowLevelItem = $this->createItem([
            'skill_level_trivial' => 20
        ]);

        $basicItem = $this->createItem([
            'skill_level_trivial' => 5
        ]);

        $this->assertEquals(0, $this->artisan('add:holy-stacks-to-items'));

        $highLevelItem = $highLevelItem->refresh();
        $lowLevelItem  = $lowLevelItem->refresh();
        $basicItem     = $basicItem->refresh();

        $this->assertEquals(20, $highLevelItem->holy_stacks);
        $this->assertEquals(1, $lowLevelItem->holy_stacks);
        $this->assertEquals(1, $basicItem->holy_stacks);
    }
}
