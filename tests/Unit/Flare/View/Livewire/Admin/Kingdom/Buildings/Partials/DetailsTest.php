<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Kingdom\Buildings\Partials;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Kingdoms\KingdomBuildings\Partials\Details;
use App\Flare\Models\GameKingdomBuilding;
use Tests\TestCase;
use Tests\Traits\CreateGameKingdomBuilding;

class DetailsTest extends TestCase
{
    use RefreshDatabase, CreateGameKingdomBuilding;

    public function testTheComponentLoads() {
        Livewire::test(Details::class)->assertSee('Name')->assertDontSee('Previous');
    }

    public function testValidationFails() {
        Livewire::test(Details::class)->call('validateInput', 'nextStep', 2)->assertHasErrors([
            'gameKingdomBuilding.name' => 'required'
        ]);
    }

    public function testCreateValidKingdomBuilding() {
        Livewire::test(Details::class)
                                    ->set('gameKingdomBuilding.name', 'Sample')
                                    ->set('gameKingdomBuilding.description', 'something')
                                    ->set('gameKingdomBuilding.max_level', 10)
                                    ->set('gameKingdomBuilding.base_durability', 10)
                                    ->set('gameKingdomBuilding.base_defence', 10)
                                    ->set('gameKingdomBuilding.required_population', 10)
                                    ->call('validateInput', 'nextStep', 2);

        // Assert building was created:
        $this->assertNotNull(GameKingdomBuilding::where('name', 'Sample')->first());
    }

    public function testUpdateKingdomBuilding() {
        $building = $this->createGameKingdomBuilding();

        Livewire::test(Details::class, ['gameKingdomBuilding' => $building])
                                        ->set('gameKingdomBuilding.name', 'Keep')
                                        ->call('validateInput', 'nextStep', 2);

        // Assert building was updated:
        $this->assertNotNull(GameKingdomBuilding::where('name', 'Keep')->first());
    }
}
