<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Locations;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Locations\Manage;
use App\Admin\Models\GameMap;
use Tests\TestCase;
use Tests\Traits\CreateLocation;

class ManageTest extends TestCase
{
    use RefreshDatabase, CreateLocation;

    public function testTheComponentLoads()
    {
        Livewire::test(Manage::class)->assertSee('Location')->assertDontSee('Previous');
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
        Livewire::test(Manage::class)->call('finish', 2, true)->assertRedirect(route('locations.list'));
    }

    public function testStoreLocation() {
        $location = $this->createLocation([
            'name'                 => 'Apples',
            'game_map_id'          => GameMap::create([
                'name' => 'Apples',
                'path' => 'test',
                'default' => true
            ])->id,
            'quest_reward_item_id' => null,
            'description'          => 'test',
            'is_port'              => false,
            'x'                    => 1,
            'y'                    => 1,
        ]);

        Livewire::test(Manage::class)->call('storeModel', $location)->assertSet('model', $location);
    }
}
