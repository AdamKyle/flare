<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Affixes;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Affixes\Manage;
use Tests\TestCase;
use Tests\Traits\CreateItemAffix;

class ManageTest extends TestCase
{
    use RefreshDatabase, CreateItemAffix;

    public function testTheComponentLoads()
    {
        Livewire::test(Manage::class)->assertSee('Affix Details')->assertSee('Affix Modifiers')->assertDontSee('Previous');
    }

    public function testTheValidationIsEmitedTo() {
        Livewire::test(Manage::class)->call('nextStep', 2, false)->assertEmitted('validateInput', 'nextStep', 2);
    }

    public function testUpdateStepIsCalled() {
        Livewire::test(Manage::class)->call('nextStep', 2, true)->assertEmitted('updateCurrentStep', 2, null);
    }

    public function testFinishCallsValidation() {
        Livewire::test(Manage::class)->call('finish', 2, false)->assertEmitted('validateInput', 'finish', 2);
    }

    public function testFinishRedirectsWhenValidationPasses() {
        Livewire::test(Manage::class)->call('finish', 2, true)->assertRedirect(route('affixes.list'));
    }

    public function testStoreLocation() {
        $affix = $this->createItemAffix();

        Livewire::test(Manage::class)->call('storeModel', $affix)->assertSet('model', $affix);
    }
}
