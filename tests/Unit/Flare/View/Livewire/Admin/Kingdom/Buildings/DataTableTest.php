<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Kingdom\Buildings;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Kingdoms\KingdomBuildings\DataTable;
use Tests\TestCase;
use Tests\Traits\CreateGameKingdomBuilding;

class DataTableTest extends TestCase
{
    use RefreshDatabase, CreateGameKingdomBuilding;

    public function setUp(): void {
        parent::setUp();

        $this->createGameKingdomBuilding();
    }

    public function testTheComponentLoads()
    {
        
        Livewire::test(DataTable::class)
            ->assertSee('Test KingdomBuilding')
            ->set('search', 'Test KingdomBuilding')
            ->assertSee('Test KingdomBuilding')
            ->set('search', 'Test cuilding')
            ->assertDontSee('Test KingdomBuilding');
    }
}
