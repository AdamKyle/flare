<?php

namespace Tests\Unit\Flare\View\Livewire\Character\Items;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\GameSkillsSeeder;
use App\Flare\View\Livewire\Character\Inventory\DataTable;
use Tests\TestCase;
use Tests\Traits\CreateAdventure;
use Tests\Traits\CreateUser;
use Tests\Setup\CharacterSetup;
use Tests\Traits\CreateItem;

class DataTableTest extends TestCase
{
    use RefreshDatabase, CreateAdventure, CreateUser, CreateItem;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $this->seed(GameSkillsSeeder::class);

        $user = $this->createUser();

        $item = $this->createItem([
            'name'        => 'Rusty Dagger',
            'type'        => 'weapon',
            'base_damage' => '6'
        ]);

        $adventure = $this->createNewAdventure();

        $this->character = (new CharacterSetup)->setupCharacter($user, ['can_move' => false])
                                        ->levelCharacterUp(10)
                                        ->giveItem($item)
                                        ->givePlayerLocation()
                                        ->createAdventureLog($adventure, [
                                            'complete'             => true,
                                            'in_progress'          => false,
                                            'last_completed_level' => 1,
                                        ])
                                        ->setSkill('Accuracy', ['skill_bonus_per_level' => 10], [
                                            'xp_towards' => 10,
                                            'currently_training' => true
                                        ])
                                        ->setSkill('Dodge', [
                                            'skill_bonus_per_level' => 10,
                                        ])
                                        ->setSkill('Looting', [
                                            'skill_bonus_per_level' => 0,
                                        ])
                                        ->getCharacter();
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
        ])->set('selected', [1])
          ->call('selectAll')
          ->set('search', 'Rusty Dagger');
    }

    public function testSelectAll() {
        $this->actingAs($this->character->user)->visit(route('game.shop.sell'))->check('select-all');
    }
}
