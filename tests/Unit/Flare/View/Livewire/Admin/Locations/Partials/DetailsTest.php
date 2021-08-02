<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Locations\Partials;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Locations\Partials\Details;
use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use Tests\TestCase;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateGameMap;

class DetailsTest extends TestCase
{
    use RefreshDatabase, CreateLocation, CreateGameMap;

    public function testTheComponentLoads() {
        Livewire::test(Details::class)->assertSee('Name')->assertDontSee('Previous');
    }

    public function testValidationFails() {
        Livewire::test(Details::class)->call('validateInput', 'nextStep', 2)->assertHasErrors([
            'location.name' => 'required'
        ]);
    }

    public function testCreateValidLocation() {
        $map = $this->createGameMap([
            'name' => 'Apples',
            'path' => 'test',
            'default' => true
        ]);

        Livewire::test(Details::class)->set('location.name', 'Sample')
                                    ->set('location.description', 'something')
                                    ->set('location.x', 10)
                                    ->set('location.y', 10)
                                    ->set('location.game_map_id', $map->id)
                                    ->call('validateInput', 'nextStep', 2);

        // Assert Location was created:
        $this->assertNotNull(Location::where('name', 'Sample')->first());
    }

    public function testUpdateMonster() {
        $location = $this->createLocation([
            'name'                 => 'Apples',
            'game_map_id'          => $this->createGameMap([
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
            'game_map_id'          => $this->createGameMap([
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
