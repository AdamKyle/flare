<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Items;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Items\Manage;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class ManageTest extends TestCase
{
    use RefreshDatabase, CreateItem;

    public function testTheComponentLoads()
    {
        Livewire::test(Manage::class)->assertSee('Item Details')->assertSee('Item Modifiers')->assertDontSee('Previous');
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
        Livewire::test(Manage::class)->call('finish', 2, true)->assertRedirect(route('items.list'));
    }

    public function testStoreLocation() {
        $item = $this->createItem();

        Livewire::test(Manage::class)->call('storeModel', $item)->assertSet('model', $item);
    }
}
