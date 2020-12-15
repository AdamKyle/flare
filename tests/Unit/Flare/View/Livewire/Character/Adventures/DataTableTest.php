<?php

namespace Tests\Unit\Flare\View\Livewire\Character\Adventures;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Character\Adventures\DataTable;
use Tests\TestCase;
use Tests\Traits\CreateAdventure;
use Tests\Traits\CreateUser;
use Tests\Setup\Character\CharacterFactory;

class DataTableTest extends TestCase
{
    use RefreshDatabase, CreateAdventure, CreateUser;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $adventure = $this->createNewAdventure();

        $this->character = (new CharacterFactory)->createBaseCharacter()
                                        ->levelCharacterUp(10)
                                        ->createAdventureLog($adventure, [
                                            'complete'             => true,
                                            'in_progress'          => false,
                                            'last_completed_level' => 1,
                                        ])
                                        ->getCharacter();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testTheComponentLoads()
    {
        
        Livewire::test(DataTable::class, [
            'adventureLogs' => $this->character->adventureLogs->load('adventure'),
        ])
        ->assertSee('Sample')
        ->set('search', 'Apples')
        ->assertDontSee('Sample')
        ->set('search', '')
        ->assertSee('Sample')
        ->call('sortBy', 'adventure.name')
        ->assertSee('Sample')
        ->set('search', 'Sample')
        ->set('sortBy', 'desc')
        ->assertSee('Sample');
    }
}
