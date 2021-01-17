<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Kingdom\Buildings\Partials;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Kingdoms\Buildings\Partials\Attributes;
use App\Flare\Models\GameBuilding;
use Tests\TestCase;
use Tests\Traits\CreateGameBuilding;

class AtrributesTest extends TestCase
{
    use RefreshDatabase, CreateGameBuilding;

    public function testTheComponentLoads() {
        Livewire::test(Attributes::class, [
            'gameBuilding' => $this->createGameBuilding()->toArray(),
        ])->assertSee('Cost in Wood:');
    }

    public function testTheComponentCallsUpdate() {
        $gameBuilding = $this->createGameBuilding();

        Livewire::test(Attributes::class)->call('update', $gameBuilding->id)->assertSet('gameBuilding.name', $gameBuilding->name);
    }

    public function testNoValidation() {
        Livewire::test(Attributes::class, [
            'gameBuilding' => $this->createGameBuilding(),
        ])->set('gameBuilding.is_walls', true)->call('validateInput', 'nextStep', 2);
        
        $this->assertNotNull(GameBuilding::where('is_walls', true)->first());
    }
}
