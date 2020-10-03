<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Items\Partials;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Locations\Partials\Location;
use App\Flare\Models\GameMap;
use App\Flare\View\Livewire\Admin\Items\Partials\Item;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class ItemTest extends TestCase
{
    use RefreshDatabase, CreateItem;

    public function testUpdateStepIsCalled() {
        $item = $this->createItem();

        Livewire::test(Item::class)->call('updateCurrentStep', 2, $item)->assertSet('item', $item)->assertSet('currentStep', 2);
    }
}
