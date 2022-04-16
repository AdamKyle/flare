<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Users;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Users\DataTable;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Setup\Character\CharacterFactory;

class DataTableTest extends TestCase
{
    use RefreshDatabase, CreateUser;

    public function setUp(): void {
        parent::setUp();

        (new CharacterFactory)->createBaseCharacter()->updateCharacter([
            'name' => 'Zex'
        ])->getCharacter(false);

        (new CharacterFactory)->createBaseCharacter()->updateCharacter([
            'name' => 'trox'
        ])->getCharacter(false);
    }

    public function testTheComponentLoads() {

        Livewire::test(DataTable::class)
                ->assertSee('Zex')
                ->assertSee('trox');
    }

    public function testTheComponentLoadsOppositeOrder() {

        Livewire::test(DataTable::class, [
            'sortAsc' => false
        ])
        ->assertSee('Zex')
        ->assertSee('trox');
    }

    public function testSortByCharacterName() {

        Livewire::test(DataTable::class)
        ->assertSee('Zex')
        ->assertSee('trox')
        ->call('sortBy', 'characters.name');
    }

    public function testSortByCharacterNameWithSearch() {

        Livewire::test(DataTable::class)
        ->assertSee('Zex')
        ->assertSee('trox')
        ->call('sortBy', 'characters.name')
        ->set('search', 'trox')
        ->assertDontSee('Zex');
    }

    public function testSearch() {

        Livewire::test(DataTable::class)
        ->assertSee('Zex')
        ->assertSee('trox')
        ->set('search', 'trox')
        ->assertDontSee('Zex');
    }

    public function testSearchForNonExistentCharacter() {

        Livewire::test(DataTable::class)
        ->assertSee('Zex')
        ->assertSee('trox')
        ->set('search', '9879879879')
        ->assertDontSee('Zex')
        ->assertDontSee('tox');
    }

    public function testSearchForCharacterNotOnline() {

        Livewire::test(DataTable::class)
        ->assertSee('Zex')
        ->assertSee('trox')
        ->set('search', 'no');
    }
}
