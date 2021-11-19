<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Kingdom\Buildings;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Kingdoms\Buildings\DataTable;
use Tests\TestCase;
use Tests\Traits\CreateGameBuilding;

class DataTableTest extends TestCase
{
    use RefreshDatabase, CreateGameBuilding;

    public function setUp(): void {
        parent::setUp();

        $this->createGameBuilding();
    }

    public function testTheComponentLoads()
    {
        
        Livewire::test(DataTable::class)
            ->assertSee('Keep')
            ->set('search', 'Keep')
            ->assertSee('Keep')
            ->set('search', 'Keet')
            ->assertDontSee('Keep');
    }
}
