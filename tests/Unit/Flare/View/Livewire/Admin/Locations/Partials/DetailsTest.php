<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Locations\Partials;

use App\Flare\Models\Monster;
use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Locations\Partials\Details;
use App\Admin\Models\GameMap;
use App\Flare\Models\Location;
use Tests\TestCase;
use Tests\Traits\CreateLocation;

class DetailsTest extends TestCase
{
    use RefreshDatabase, CreateLocation;

    public function testTheComponentLoads() {
        Livewire::test(Details::class)->assertSee('Name')->assertDontSee('Previous');
    }

    public function testValidationFails() {
        Livewire::test(Details::class)->call('validateInput', 'nextStep', 2)->assertHasErrors([
            'location.name' => 'required'
        ]);
    }

    public function testCreateValidMonster() {
        GameMap::create([
            'name' => 'Apples',
            'path' => 'test',
            'default' => true
        ]);

        Livewire::test(Details::class)->set('location.name', 'Sample')
                                    ->set('location.description', 'something')
                                    ->set('location.x', 10)
                                    ->set('location.y', 10)
                                    ->set('location.game_map_id', 1)
                                    ->call('validateInput', 'nextStep', 2);

        // Assert Location was created:
        $this->assertNotNull(Location::where('name', 'Sample')->first());
    }

    public function testUpdateMonster() {
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

        Livewire::test(Details::class, ['location' => $location])
                                    ->set('location.name', 'Sample')
                                    ->set('location.description', $location->description)
                                    ->set('location.x', $location->x)
                                    ->set('location.y', $location->y)
                                    ->set('location.game_map_id', $location->map->id)
                                    ->call('validateInput', 'nextStep', 2);

        // Assert location was updated:
        $this->assertNotNull(Location::where('name', 'Sample')->first());
    }

    public function testInitialMonsterIsArray() {
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
        
        Livewire::test(Details::class, ['location' => $location->toArray()])->assertSet('location.name', $location->name);
    }
}
