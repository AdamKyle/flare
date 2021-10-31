<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Locations;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Locations\DataTable;
use Tests\TestCase;
use Tests\Traits\CreateAdventure;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateGameSkill;
use Tests\traits\CreateGameMap;

class DataTableTest extends TestCase
{
    use RefreshDatabase, CreateLocation, CreateAdventure, CreateGameSkill, CreateGameMap;

    public function setUp(): void {
        parent::setUp();

        $this->createGameSkill();
    }

    public function testTheComponentLoads()
    {
        $this->createLocation([
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

        $this->createLocation([
            'name'                 => 'Bananas',
            'game_map_id'          => $this->createGameMap([
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

    public function testTheComponentLoadsWithAdventure()
    {
        $adventure = $this->createNewAdventure();

        $locationA = $this->createLocation([
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

        $locationA->adventures()->attach($adventure->id);

        $locationB = $this->createLocation([
            'name'                 => 'Bananas',
            'game_map_id'          => $this->createGameMap([
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

        $locationB->adventures()->attach($adventure->id);
        
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

        $this->createLocation([
            'name'                 => 'Bananas',
            'game_map_id'          => $this->createGameMap([
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

    public function testTheComponentSearchesOnMapName()
    {
        $this->createLocation([
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

        $this->createLocation([
            'name'                 => 'Bananas',
            'game_map_id'          => $this->createGameMap([
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
            ->set('search', 'Bananas')
            ->assertSee('Bananas')
            ->assertSee('fa-sort-up');
    }

    public function testTheComponentSearchesOnMapNameWithAdventure()
    {
        $adventure = $this->createNewAdventure();

        $locationA = $this->createLocation([
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

        $locationA->adventures()->attach($adventure->id);

        $locationB = $this->createLocation([
            'name'                 => 'Bananas',
            'game_map_id'          => $this->createGameMap([
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

        $locationB->adventures()->attach($adventure->id);
        
        Livewire::test(DataTable::class, [
            'adventureId' => $adventure->id
        ])
        ->assertSee('Apples')
        ->assertSee('Bananas')
        ->call('sortBy', 'game_maps.name')
        ->set('search', 'Bananas')
        ->assertSee('Bananas')
        ->assertSee('fa-sort-up');
    }
}
