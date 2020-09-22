<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Locations;

use App\Admin\Models\GameMap;
use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Locations\DataTable;
use Tests\TestCase;
use Tests\Traits\CreateLocation;

class DataTableTest extends TestCase
{
    use RefreshDatabase, CreateLocation;

    public function testTheComponentLoads()
    {
        $this->createLocation([
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

        $this->createLocation([
            'name'                 => 'Bananas',
            'game_map_id'          => GameMap::create([
                'name' => 'Bananas',
                'path' => 'test',
                'default' => true
            ])->id,
            'quest_reward_item_id' => null,
            'description'          => 'test',
            'is_port'              => false,
            'x'                    => 2,
            'y'                    => 2,
        ]);
        
        Livewire::test(DataTable::class)
            ->assertSee('Apples')
            ->assertSee('Bananas')
            ->set('search', 'Apples')
            ->assertSee('Apples')
            ->assertDontSee('Bananas');
    }

    public function testTheComponentFiltersOnMapName()
    {
        $this->createLocation([
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

        $this->createLocation([
            'name'                 => 'Bananas',
            'game_map_id'          => GameMap::create([
                'name' => 'Bananas',
                'path' => 'test',
                'default' => true
            ])->id,
            'quest_reward_item_id' => null,
            'description'          => 'test',
            'is_port'              => false,
            'x'                    => 2,
            'y'                    => 2,
        ]);
        
        Livewire::test(DataTable::class)
            ->assertSee('Apples')
            ->assertSee('Bananas')
            ->call('sortBy', 'game_maps.name')
            ->assertSee('fa-sort-up');
    }

    public function testTheComponentFiltersOnRegular()
    {
        $this->createLocation([
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

        $this->createLocation([
            'name'                 => 'Bananas',
            'game_map_id'          => GameMap::create([
                'name' => 'Bananas',
                'path' => 'test',
                'default' => true
            ])->id,
            'quest_reward_item_id' => null,
            'description'          => 'test',
            'is_port'              => false,
            'x'                    => 2,
            'y'                    => 2,
        ]);
        
        Livewire::test(DataTable::class)
            ->assertSee('Apples')
            ->assertSee('Bananas')
            ->call('sortBy', 'name')
            ->assertSee('fa-sort-up');
    }
}
