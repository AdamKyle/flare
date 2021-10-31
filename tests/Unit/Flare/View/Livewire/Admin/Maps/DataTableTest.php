<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Maps;


use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Maps\DataTable;
use Tests\Traits\CreateGameMap;
use Tests\TestCase;

class DataTableTest extends TestCase
{
    use RefreshDatabase, CreateGameMap;

    public function testTheComponentLoads()
    {
        $this->createGameMap([
            'name' => 'Apples',
            'path' => 'test',
            'default' => true
        ]);

        $this->createGameMap([
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
