<?php

namespace Tests\Unit\Flare\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Flare\Models\ItemAffix;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class ItemAffixTest extends TestCase
{
    use RefreshDatabase, CreateItem;


    public function testItemAffixCanGetParentItem()
    {
        $item = $this->createItem([
            'name'        => 'Rusty Dagger',
            'type'        => 'weapon',
            'base_damage' => '6'
        ]);

        $item->itemAffixes()->create([
            'item_id' => $item->id,
            'name' => 'test',
            'base_damage_mod' => 'str',
            'type' => 'suffix',
            'description' => 'test',
        ]);

        $this->assertEquals($item->id, ItemAffix::first()->item->id);
    }
}
