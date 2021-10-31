<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Kingdom\Units\Partials;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Flare\View\Livewire\Admin\Kingdoms\Units\Partials\Details;
use App\Flare\Models\GameUnit;
use Tests\Traits\CreateGameUnit;

class DetailsTest extends TestCase
{
    use RefreshDatabase, CreateGameUnit;

    public function testTheComponentLoads() {
        Livewire::test(Details::class)->assertSee('Name')->assertDontSee('Previous');
    }

    public function testCreateBasicUnit() {
        Livewire::test(Details::class)->set('gameUnit.name', 'Sample Name')
                                      ->set('gameUnit.description', 'Test Description')
                                      ->set('gameUnit.attack', 2)
                                      ->set('gameUnit.defence', 3)
                                      ->set('gameUnit.travel_time', 1)
                                      ->set('gameUnit.required_population', 10)
                                      ->set('gameUnit.time_to_recruit', 2)
                                      ->set('gameUnit.wood_cost', 1)
                                      ->set('gameUnit.clay_cost', 1)
                                      ->set('gameUnit.stone_cost', 1)
                                      ->set('gameUnit.iron_cost', 1)
                                      ->call('validateInput', 'nextStep', 2);

        $gameUnit = GameUnit::first();

        $this->assertTrue(!is_null($gameUnit));
    }

    public function testEditBasicUnit() {
        Livewire::test(Details::class, [
            'gameUnit' => $this->createGameUnit(),
            'editing'  => true,
        ])->set('gameUnit.name', 'Sample Name')
          ->set('gameUnit.description', 'Test Description')
          ->set('gameUnit.attack', 2)
          ->set('gameUnit.defence', 3)
          ->set('gameUnit.travel_time', 1)
          ->set('gameUnit.required_population', 10)
          ->set('gameUnit.time_to_recruit', 2)
          ->set('gameUnit.wood_cost', 1)
          ->set('gameUnit.clay_cost', 1)
          ->set('gameUnit.stone_cost', 1)
          ->set('gameUnit.iron_cost', 1)
          ->call('validateInput', 'nextStep', 2)
          ->assertSet('editing', true);

        $gameUnit = GameUnit::first();

        $this->assertTrue($gameUnit->name === 'Sample Name');
    }

    public function testValidationOfPrimaryAndFallBackTargetFails() {
        Livewire::test(Details::class)->set('gameUnit.name', 'Sample Name')
                                      ->set('gameUnit.description', 'Test Description')
                                      ->set('gameUnit.attack', 2)
                                      ->set('gameUnit.defence', 3)
                                      ->set('gameUnit.travel_time', 1)
                                      ->set('gameUnit.required_population', 10)
                                      ->set('gameUnit.time_to_recruit', 2)
                                      ->set('gameUnit.wood_cost', 1)
                                      ->set('gameUnit.clay_cost', 1)
                                      ->set('gameUnit.stone_cost', 1)
                                      ->set('gameUnit.iron_cost', 1)
                                      ->set('gameUnit.primary_target', 'walls')
                                      ->set('gameUnit.fall_back', 'walls')
                                      ->call('validateInput', 'nextStep', 2)
                                      ->assertSee('Cannot have the same fallback target as the primary target.');
    }
}
