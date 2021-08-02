<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Items\Partials;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Items\Partials\ItemModifiers;
use App\Flare\Models\Item;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class ItemModifiersTest extends TestCase
{
    use RefreshDatabase, CreateItem;

    public function testTheComponentLoads() {
        Livewire::test(ItemModifiers::class, [
            'item' => $this->createItem(),
        ])->assertSee('Str Mod:');
    }

    public function testTheComponentCallsUpdate() {
        $item = $this->createItem();

        Livewire::test(ItemModifiers::class)->call('update', $item->id)->assertSet('item.name', $item->name);
    }

    public function testNoValidation() {
        Livewire::test(ItemModifiers::class, [
            'item' => $this->createItem(),
        ])->call('validateInput', 'nextStep', 2);


        // Assert Item modifier was not created:
        $this->assertNull(Item::where('effect', 'walk-on-water')->first());
    }

    public function TestAddModifier() {
        Livewire::test(ItemModifiers::class, [
            'item' => $this->createItem(),
        ])->set('item.effects', 'walk-on-water')
          ->call('validateInput', 'nextStep', 2);


        // Assert Item modifier was created:
        $this->assertNotNull(Item::where('effect', 'walk-on-water')->first());
    }

    public function testInitialAffixIsArray() {
        $item = $this->createItem();

        Livewire::test(ItemModifiers::class, ['item' => $item->toArray()])->assertSet('item.name', $item->name);
    }
}
