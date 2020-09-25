<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Locations\Partials;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Locations\Partials\Location;
use App\Flare\Models\GameMap;
use Tests\TestCase;
use Tests\Traits\CreateLocation;

class LocationTest extends TestCase
{
    use RefreshDatabase, CreateLocation;

    public function testUpdateStepIsCalled() {
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

        Livewire::test(Location::class)->call('updateCurrentStep', 2, $location)->assertSet('location', $location)->assertSet('currentStep', 2);
    }
}
