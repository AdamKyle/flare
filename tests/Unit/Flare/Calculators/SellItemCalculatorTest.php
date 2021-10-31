<?php

namespace Tests\Unit\Flare\Calculators;

use Facades\App\Flare\Calculators\SellItemCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class SellItemCalculatorTest extends TestCase
{
    use RefreshDatabase, CreateItem, CreateItemAffix;

    public function testSellItemCalculator() {
        $price = SellItemCalculator::fetchTotalSalePrice(
            $this->createItem([
                'item_suffix_id' => $this->createItemAffix([
                    'type' => 'suffix'
                ])->id,
                'item_prefix_id' => $this->createItemAffix([
                    'type' => 'prefix'
                ])->id,
            ])
        );

        $this->assertTrue($price > 0);
    }

    public function testSellItemWithAffixesCalculator() {
        $price = SellItemCalculator::fetchSalePriceWithAffixes(
            $this->createItem([
                'item_suffix_id' => $this->createItemAffix([
                    'type' => 'suffix'
                ])->id,
                'item_prefix_id' => $this->createItemAffix([
                    'type' => 'prefix'
                ])->id,
            ])
        );

        $this->assertTrue($price > 0);
    }
}
