<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Classes;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\GameClass;
use App\Flare\View\Livewire\Admin\Classes\DataTable;
use Tests\Setup\CharacterSetup;
use Tests\TestCase;
use Tests\Traits\CreateClass;

class DataTableTest extends TestCase
{
    use RefreshDatabase, CreateClass;

    public function testTheComponentLoads() {
        Livewire::test(DataTable::class)->assertSee('Name');
    }

    public function testTheComponentSearches() {
        $class = $this->createClass();

        Livewire::test(DataTable::class)->set('search', $class->name)->assertSee($class->name);
    }

    public function testTheComponentSearchesEmpty() {
        $class = $this->createClass();

        Livewire::test(DataTable::class)->set('search', 'Apples')->assertDontSee($class->name);
    }
}