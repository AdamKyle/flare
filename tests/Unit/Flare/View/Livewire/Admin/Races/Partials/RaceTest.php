<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Races\Partials;

use App\Flare\Models\Character;
use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\GameRace;
use App\Flare\View\Livewire\Admin\Races\Partials\Race;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateUser;

class RaceTest extends TestCase
{
    use RefreshDatabase, CreateRace, CreateUser;

    public function testTheComponentLoads() {
        Livewire::test(Race::class)->assertSee('Name');
    }

    public function testValidationFails() {
        Livewire::test(Race::class)
            ->call('validateInput', 'nextStep', 2)
            ->assertHasErrors('race.name');
    }

    public function testCreateValidRace() {
        Livewire::test(Race::class)
                 ->set('race.name', 'Sample')   
                 ->call('validateInput', 'nextStep', 2);
            

        $this->assertTrue(!is_null(GameRace::where('name', 'Sample')->first()));
    }

    public function testChangeRaceStats() {
        
        $gameRace = $this->createRace();

        Livewire::test(Race::class, [
            'race' => $gameRace
        ])
            ->call('validateInput', 'nextStep', 2)
            ->set('race.name', 'New Race')
            ->set('race.str_mod', 100)
            ->call('validateInput', 'nextStep', 2);

        $gameRace = $gameRace->refresh();

        $this->assertEquals('New Race', $gameRace->name);
        $this->assertEquals(100, $gameRace->str_mod);
    }
}