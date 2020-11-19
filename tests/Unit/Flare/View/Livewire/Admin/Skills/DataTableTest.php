<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Skills;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Skills\DataTable;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;

class DataTableTest extends TestCase
{
    use RefreshDatabase, CreateGameSkill;

    public function testTheComponentLoads() {
        Livewire::test(DataTable::class)->assertSee('Name');
    }

    public function testTheComponentSearches() {
        $skill = $this->createGameSkill();

        Livewire::test(DataTable::class)->set('search', 'yes')->assertSee($skill->name);
    }

    public function testTheComponentSearchesEmpty() {
        $skill = $this->createGameSkill();

        Livewire::test(DataTable::class)->set('search', 'no')->assertDontSee($skill->name);
    }
}