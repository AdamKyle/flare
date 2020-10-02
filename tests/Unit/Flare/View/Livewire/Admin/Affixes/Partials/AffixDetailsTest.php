<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Affixes\Partials;

use App\Flare\Models\ItemAffix;
use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Affixes\Partials\AffixDetails;
use Tests\TestCase;
use Tests\Traits\CreateItemAffix;

class AffixDetailsTest extends TestCase
{
    use RefreshDatabase, CreateItemAffix;

    public function testTheComponentLoads() {
        Livewire::test(AffixDetails::class)->assertSee('Name')->assertDontSee('Previous');
    }

    public function testValidationFails() {
        Livewire::test(AffixDetails::class)->call('validateInput', 'nextStep', 2)->assertHasErrors([
            'itemAffix.name' => 'required'
        ]);
    }

    public function testCreateValidAffix() {
        Livewire::test(AffixDetails::class)
                                    ->set('itemAffix.name', 'Sample')
                                    ->set('itemAffix.description', 'something')
                                    ->set('itemAffix.type', 10)
                                    ->set('itemAffix.cost', 10)
                                    ->call('validateInput', 'nextStep', 2);

        // Assert Affix was created:
        $this->assertNotNull(ItemAffix::where('name', 'Sample')->first());
    }

    public function testUpdateAffix() {
        $affix = $this->createItemAffix();

        Livewire::test(AffixDetails::class, ['itemAffix' => $affix])
                                        ->set('itemAffix.name', 'Apple Sauce')
                                        ->set('itemAffix.description', $affix->description)
                                        ->set('itemAffix.type', $affix->type)
                                        ->set('itemAffix.cost', $affix->cost)
                                        ->call('validateInput', 'nextStep', 2);

        // Assert affix was updated:
        $this->assertNotNull(ItemAffix::where('name', 'Apple Sauce')->first());
    }

    public function testInitialAffixIsArray() {
        $affix = $this->createItemAffix();
        
        Livewire::test(AffixDetails::class, ['itemAffix' => $affix->toArray()])->assertSet('itemAffix.name', $affix->name);
    }
}
