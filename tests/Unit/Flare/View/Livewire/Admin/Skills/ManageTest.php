<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Skills;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Skills\Manage;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class ManageTest extends TestCase
{
    use RefreshDatabase, CreateGameSkill;

    public function testTheComponentLoads()
    {
        Livewire::test(Manage::class)->assertSee('Skill Details')->assertSee('Skill Modifiers')->assertDontSee('Previous');
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
        Livewire::test(Manage::class)->call('finish', 2, true)->assertRedirect(route('skills.list'));
    }

    public function testStoreLocation() {
        $skill = $this->createGameSkill();

        Livewire::test(Manage::class)->call('storeModel', $skill)->assertSet('model', $skill);
    }
}
