<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Affixes\Partials;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Locations\Partials\Location;
use App\Flare\Models\GameMap;
use App\Flare\View\Livewire\Admin\Affixes\Partials\Affix;
use Tests\TestCase;
use Tests\Traits\CreateItemAffix;

class AffixTest extends TestCase
{
    use RefreshDatabase, CreateItemAffix;

    public function testUpdateStepIsCalled() {
        $affix = $this->createItemAffix();

        Livewire::test(Affix::class)->call('updateCurrentStep', 2, $affix)->assertSet('itemAffix', $affix)->assertSet('currentStep', 2);
    }
}
