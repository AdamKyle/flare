<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Affixes\Partials;

use App\Flare\Models\ItemAffix;
use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Affixes\Partials\AffixDetails;
use App\Flare\View\Livewire\Admin\Affixes\Partials\AffixModifier;
use Tests\TestCase;
use Tests\Traits\CreateItemAffix;

class AffixModifiersTest extends TestCase
{
    use RefreshDatabase, CreateItemAffix;

    public function testTheComponentLoads() {
        Livewire::test(AffixModifier::class, [
            'itemAffix' => $this->createItemAffix(),
        ])->assertSee('Str Mod:');
    }

    public function testTheComponentCallsUpdate() {
        $itemAffix = $this->createItemAffix();

        Livewire::test(AffixModifier::class)->call('update', $itemAffix->id)->assertSet('itemAffix.name', $itemAffix->name);
    }

    public function testValidationFails() {
        Livewire::test(AffixModifier::class, [
            'itemAffix' => $this->createItemAffix(),
        ])->set('itemAffix.skill_name', 'Looting')
          ->call('validateInput', 'nextStep', 2)
          ->assertSee('Must have a valid value since you selected a skill');
    }

    public function testCreateValidAffixModifier() {
        Livewire::test(AffixModifier::class, [
            'itemAffix' => $this->createItemAffix(),
        ])->set('itemAffix.skill_name', 'Looting')
          ->set('itemAffix.skill_training_bonus', 0.20)
          ->call('validateInput', 'nextStep', 2);
          

        // Assert Affix Modifier was created:
        $this->assertNotNull(ItemAffix::where('skill_name', 'Looting')->first());
    }

    public function testInitialAffixIsArray() {
        $affix = $this->createItemAffix();
        
        Livewire::test(AffixModifier::class, ['itemAffix' => $affix->toArray()])->assertSet('itemAffix.name', $affix->name);
    }
}
