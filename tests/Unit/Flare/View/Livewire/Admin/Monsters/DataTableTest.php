<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Monsters;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Monsters\DataTable;
use Database\Seeders\GameSkillsSeeder;
use Tests\TestCase;
use Tests\Traits\CreateMonster;

class DataTableTest extends TestCase
{
    use RefreshDatabase, CreateMonster;
    

    public function setUp(): void {
        parent::setUp();

        $this->seed(GameSkillsSeeder::class);
    }

    public function testTheComponentLoads()
    {
        $this->createMonster([
            'name' => 'Apples'
        ]);

        $this->createMonster([
            'name' => 'Bananas'
        ]);
        
        Livewire::test(DataTable::class)
            ->assertSee('Apples')
            ->assertSee('Bananas')
            ->set('search', 'Apples')
            ->assertSee('Apples')
            ->assertDontSee('Bananas');
    }
}
