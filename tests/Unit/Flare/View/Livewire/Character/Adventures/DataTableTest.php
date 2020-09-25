<?php

namespace Tests\Unit\Flare\View\Livewire\Charcater\Adventures;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Character\Adventures\DataTable;
use Tests\TestCase;
use Tests\Traits\CreateAdventure;
use Tests\Traits\CreateUser;
use Tests\Setup\CharacterSetup;

class DataTableTest extends TestCase
{
    use RefreshDatabase, CreateAdventure, CreateUser;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $user = $this->createUser();

        $adventure = $this->createNewAdventure();

        $this->character = (new CharacterSetup)->setupCharacter($user, ['can_move' => false])
                                        ->levelCharacterUp(10)
                                        ->createAdventureLog($adventure, [
                                            'complete'             => true,
                                            'in_progress'          => false,
                                            'last_completed_level' => 1,
                                        ])
                                        ->setSkill('Accuracy', [
                                            'skill_bonus' => 10,
                                            'xp_towards' => 10,
                                            'currently_training' => true
                                        ])
                                        ->setSkill('Dodge', [
                                            'skill_bonus' => 10,
                                        ])
                                        ->setSkill('Looting', [
                                            'skill_bonus' => 0,
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
        ->set('sortAsc', false)
        ->call('sortBy', 'name')
        ->assertSee('Sample')
        ->set('search', 'Sample')
        ->assertSee('Sample');
    }
}
