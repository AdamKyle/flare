<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\PassiveSkills;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\PassiveSkills\DataTable;
use Tests\TestCase;
use Tests\Traits\CreatePassiveSkill;

class DataTableTest extends TestCase
{
    use RefreshDatabase, CreatePassiveSkill;

    public function testTheComponentLoads() {
        Livewire::test(DataTable::class)->assertSee('Name');
    }

    public function testTheComponentSearches() {
        $skill = $this->createPassiveSkill();

        Livewire::test(DataTable::class)->set('search', $skill->name)->assertSee($skill->name);
    }

    public function testTheComponentSearchesEmpty() {
        $skill = $this->createPassiveSkill();

        Livewire::test(DataTable::class)->set('search', 'no')->assertDontSee($skill->name);
    }
}