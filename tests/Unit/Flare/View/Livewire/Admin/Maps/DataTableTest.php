<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Maps;


use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Maps\DataTable;
use App\Flare\Models\GameMap;
use Tests\TestCase;

class DataTableTest extends TestCase
{
    use RefreshDatabase;

    public function testTheComponentLoads()
    {
        GameMap::create([
            'name' => 'Apples',
            'path' => 'test',
            'default' => true
        ]);

        GameMap::create([
            'name' => 'Bananas',
            'path' => 'test',
            'default' => true
        ]);
        
        Livewire::test(DataTable::class)
            ->assertSee('Apples')
            ->assertSee('Bananas')
            ->set('search', 'Apples')
            ->assertSee('Apples')
            ->assertDontSee('Bananas')
            ->call('sortBy', 'name');
    }
}
