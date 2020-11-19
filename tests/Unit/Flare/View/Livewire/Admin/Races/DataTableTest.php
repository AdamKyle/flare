<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Races;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\GameClass;
use App\Flare\View\Livewire\Admin\Races\DataTable;
use Tests\Setup\CharacterSetup;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateRace;

class DataTableTest extends TestCase
{
    use RefreshDatabase, CreateRace;

    public function testTheComponentLoads() {
        Livewire::test(DataTable::class)->assertSee('Name');
    }

    public function testTheComponentSearches() {
        $race = $this->createRace();

        Livewire::test(DataTable::class)->set('search', $race->name)->assertSee($race->name);
    }

    public function testTheComponentSearchesEmpty() {
        $race = $this->createRace();

        Livewire::test(DataTable::class)->set('search', 'Apples')->assertDontSee($race->name);
    }
}