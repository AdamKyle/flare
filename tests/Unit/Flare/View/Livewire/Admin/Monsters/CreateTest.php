<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Monsters;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Monsters\Create;
use Tests\TestCase;
use Tests\Traits\CreateMonster;

class CreateTest extends TestCase
{
    use RefreshDatabase, CreateMonster;

    public function testTheComponentLoads()
    {
        Livewire::test(Create::class)->assertSee('Monster')->assertDontSee('Previous');
    }

    public function testTheValidationIsEmitedTo() {
        Livewire::test(Create::class)->call('nextStep', 2, false)->assertEmitted('validateInput', 'nextStep', 2);
    }

    public function testUpdateStepIsCalled() {
        Livewire::test(Create::class)->call('nextStep', 2, true)->assertEmitted('updateCurrentStep', 2, null);
    }

    public function testFinishCallsValidation() {
        Livewire::test(Create::class)->call('finish', 2, false)->assertEmitted('validateInput', 'finish', 2);
    }

    public function testFinishRedirectsWhenValidationPasses() {
        Livewire::test(Create::class)->call('finish', 2, true)->assertRedirect(route('monsters.list'));
    }

    public function testStoreMonster() {
        $monster = $this->createMonster();

        Livewire::test(Create::class)->call('storeModel', $monster)->assertSet('model', $monster);
    }
}
