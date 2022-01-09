<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Adventures;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Adventures\DataTable;
use Tests\TestCase;
use Tests\Traits\CreateAdventure;
use Tests\Traits\CreateGameSkill;

class DataTableTest extends TestCase
{
    use RefreshDatabase, CreateAdventure, CreateGameSkill;

    public function setUp(): void {
        parent::setUp();

        $this->createGameSkill();
    }

    public function testTheComponentLoads()
    {
        $this->createNewAdventure(null, null, 1, 'Apples');

        $this->createNewAdventure(null, null, 10, 'Bananas');
        
        Livewire::test(DataTable::class)
            ->assertSee('Apples')
            ->assertSee('Bananas')
            ->set('search', 'Apples')
            ->assertSee('Apples')
            ->assertDontSee('Bananas');
    }
}
