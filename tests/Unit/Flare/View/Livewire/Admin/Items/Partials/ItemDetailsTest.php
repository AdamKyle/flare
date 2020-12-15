<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Items\Partials;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Item;
use App\Flare\View\Livewire\Admin\Items\Partials\ItemDetails;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class ItemDetailsTest extends TestCase
{
    use RefreshDatabase, CreateItem;

    public function testTheComponentLoads() {
        Livewire::test(ItemDetails::class)->assertSee('Name')->assertDontSee('Previous');
    }

    public function testValidationFails() {
        Livewire::test(ItemDetails::class)->call('validateInput', 'nextStep', 2)->assertHasErrors([
            'item.name' => 'required'
        ]);
    }

    public function testSecondaryValidationRules() {
        Livewire::test(ItemDetails::class)
        ->set('item.name', 'Sample')
        ->set('item.type', 'weapon')
        ->set('item.description', 'test')
        ->set('item.can_craft', true)
        ->set('item.skill_name', 'Looting')
        ->call('validateInput', 'nextStep', 2)
        ->assertSee('Cannot be empty when you said this item is craftable.')
        ->assertSee('Must have a skill level required to craft.')
        ->assertSee('Must have a skill trivial level.')
        ->assertSee('You cannot say this item affects skill training and not say by how much.')
        ->assertSee('How much does this item cost?');
    }

    public function testCreateValidItem() {
        Livewire::test(ItemDetails::class)
                                    ->set('item.name', 'Sample')
                                    ->set('item.description', 'something')
                                    ->set('item.type', 10)
                                    ->set('item.cost', 10)
                                    ->call('validateInput', 'nextStep', 2);

        // Assert Affix was created:
        $this->assertNotNull(Item::where('name', 'Sample')->first());
    }

    public function testUpdateItem() {
        $item = $this->createItem();

        Livewire::test(ItemDetails::class, ['item' => $item])
                                        ->set('item.name', 'Apple Sauce')
                                        ->set('item.description', $item->description)
                                        ->set('item.type', $item->type)
                                        ->set('item.cost', $item->cost)
                                        ->call('validateInput', 'nextStep', 2);

        // Assert affix was updated:
        $this->assertNotNull(Item::where('name', 'Apple Sauce')->first());
    }
}
