<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Monsters;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Monsters\DataTable;
use Tests\TestCase;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateGameSkill;

class DataTableTest extends TestCase
{
    use RefreshDatabase, CreateMonster, CreateGameSkill;
    

    public function setUp(): void {
        parent::setUp();

        $this->createGameSkill();
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
