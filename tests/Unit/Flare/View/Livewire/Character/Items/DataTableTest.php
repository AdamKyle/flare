<?php

namespace Tests\Unit\Flare\View\Livewire\Character\Items;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Character\Inventory\DataTable;
use Tests\TestCase;
use Tests\Traits\CreateAdventure;
use Tests\Traits\CreateUser;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateItem;

class DataTableTest extends TestCase
{
    use RefreshDatabase, CreateAdventure, CreateUser, CreateItem;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $item = $this->createItem([
            'name'        => 'Rusty Dagger',
            'type'        => 'weapon',
            'base_damage' => '6'
        ]);

        $adventure = $this->createNewAdventure();

        $this->character = (new CharacterFactory)->createBaseCharacter()
                                        ->givePlayerLocation()
                                        ->levelCharacterUp(10)
                                        ->inventoryManagement()
                                        ->giveItem($item)
                                        ->getCharacterFactory()
                                        ->givePlayerLocation()
                                        ->createAdventureLog($adventure, [
                                            'complete'             => true,
                                            'in_progress'          => false,
                                            'last_completed_level' => 1,
                                        ])
                                        ->getCharacter(false);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testTheComponentLoads()
    {
        $this->actingAs($this->character->user);
        
        Livewire::test(DataTable::class, [
            'batchSell' => true,
            'character' => $this->character,
        ])->set('selected', [1])
          ->call('selectAll')
          ->set('search', 'Rusty Dagger');
    }

    public function testSelectAll() {
        $this->actingAs($this->character->user)->visit(route('game.shop.sell', ['character' => $this->character->id]))->check('select-all');
    }
}
