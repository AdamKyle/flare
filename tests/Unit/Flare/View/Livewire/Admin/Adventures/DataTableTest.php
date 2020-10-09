<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Adventures;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Adventures\DataTable;
use Database\Seeders\GameSkillsSeeder;
use Tests\TestCase;
use Tests\Traits\CreateAdventure;

class DataTableTest extends TestCase
{
    use RefreshDatabase, CreateAdventure;

    public function setUp(): void {
        parent::setUp();

        $this->seed(GameSkillsSeeder::class);
    }

    public function testTheComponentLoads()
    {
        $this->createNewAdventure(null, 1, 'Apples');

        $this->createNewAdventure(null, 10, 'Bananas');
        
        Livewire::test(DataTable::class)
            ->assertSee('Apples')
            ->assertSee('Bananas')
            ->set('search', 'Apples')
            ->assertSee('Apples')
            ->assertDontSee('Bananas');
    }
}
