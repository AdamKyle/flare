<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Kingdom\Buildings\Partials;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Kingdoms\Buildings\Partials\Details;
use App\Flare\Models\GameBuilding;
use Tests\TestCase;
use Tests\Traits\CreateGameBuilding;

class DetailsTest extends TestCase
{
    use RefreshDatabase, CreateGameBuilding;

    public function testTheComponentLoads() {
        Livewire::test(Details::class)->assertSee('Name')->assertDontSee('Previous');
    }

    public function testValidationFails() {
        Livewire::test(Details::class)->call('validateInput', 'nextStep', 2)->assertHasErrors([
            'gameBuilding.name' => 'required'
        ]);
    }

    public function testCreateValidBuilding() {
        Livewire::test(Details::class)
                                    ->set('gameBuilding.name', 'Sample')
                                    ->set('gameBuilding.description', 'something')
                                    ->set('gameBuilding.max_level', 10)
                                    ->set('gameBuilding.base_durability', 10)
                                    ->set('gameBuilding.base_defence', 10)
                                    ->set('gameBuilding.required_population', 10)
                                    ->call('validateInput', 'nextStep', 2);

        // Assert building was created:
        $this->assertNotNull(GameBuilding::where('name', 'Sample')->first());
    }

    public function testUpdateBuilding() {
        $building = $this->createGameBuilding();

        Livewire::test(Details::class, ['gameBuilding' => $building])
                                        ->set('gameBuilding.name', 'Keep')
                                        ->call('validateInput', 'nextStep', 2);

        // Assert building was updated:
        $this->assertNotNull(GameBuilding::where('name', 'Keep')->first());
    }
}
