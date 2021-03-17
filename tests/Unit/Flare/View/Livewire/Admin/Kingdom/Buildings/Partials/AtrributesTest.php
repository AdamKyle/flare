<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Kingdom\Buildings\Partials;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Kingdoms\KingdomBuildings\Partials\Attributes;
use App\Flare\Models\GameKingdomBuilding;
use Tests\TestCase;
use Tests\Traits\CreateGameKingdomBuilding;
use Tests\Traits\CreateGameUnit;

class AtrributesTest extends TestCase
{
    use RefreshDatabase, CreateGameKingdomBuilding, CreateGameUnit;

    public function testTheComponentLoads() {
        Livewire::test(Attributes::class, [
            'gameKingdomBuilding' => $this->createGameKingdomBuilding()->toArray(),
        ])->assertSee('Cost in Wood:');
    }

    public function testTheComponentLoadsWithUnitSelectionDisabled() {
        $gameKingdomBuilding = $this->createGameKingdomBuilding();

        $gameKingdomBuilding->update([
            'trains_units' => true,
        ]);

        Livewire::test(Attributes::class, [
            'gameKingdomBuilding' => $gameKingdomBuilding->refresh()
        ])->assertSee('Cost in Wood:');
    }

    public function testTheComponentLoadsWithUnitSelectionNotDisabled() {
        $gameKingdomBuilding = $this->createGameKingdomBuilding();

        $gameKingdomBuilding->update([
            'trains_units' => true,
        ]);

        $gameKingdomBuilding->units()->create([
            'game_building_id' => $gameKingdomBuilding->id,
            'game_unit_id'     => $this->createGameUnit()->id,
            'required_level'   => 1,
        ]);

        Livewire::test(Attributes::class, [
            'gameKingdomBuilding' => $gameKingdomBuilding->refresh()
        ])->assertSee('Cost in Wood:');
    }

    public function testTheComponentCallsUpdate() {
        $gameKingdomBuilding = $this->createGameKingdomBuilding();

        Livewire::test(Attributes::class)->call('update', $gameKingdomBuilding->id)->assertSet('gameKingdomBuilding.name', $gameKingdomBuilding->name);
    }

    public function testNoValidation() {
        Livewire::test(Attributes::class, [
            'gameKingdomBuilding' => $this->createGameKingdomBuilding(),
        ])->set('gameKingdomBuilding.is_walls', true)->call('validateInput', 'nextStep', 2);
        
        $this->assertNotNull(GameKingdomBuilding::where('is_walls', true)->first());
    }

    public function testSelectedUnitsWithNoPerLevelValidationError() {
        $unit = $this->createGameUnit();

        Livewire::test(Attributes::class, [
            'gameKingdomBuilding' => $this->createGameKingdomBuilding(),
        ])->set('selectedUnits', [$unit->id])->call('validateInput', 'nextStep', 2)->assertSee('How many levels between units?');
    }

    public function testSelectedUnitsValidationFails() {
        $unit = $this->createGameUnit();

        Livewire::test(Attributes::class, [
            'gameKingdomBuilding' => $this->createGameKingdomBuilding(),
        ])->set('selectedUnits', [$unit->id])
          ->set('gameKingdomBuilding.units_per_level', 30)
          ->call('validateInput', 'nextStep', 2)
          ->assertSee('Your selected units and units per level are greator then your max level.');
    }

    public function testIsEditing() {
        Livewire::test(Attributes::class, [
            'gameKingdomBuilding' => $this->createGameKingdomBuilding(),
            'editing'      => true,
        ])->call('validateInput', 'nextStep', 2)
          ->assertSet('editing', true);
    }

    public function testAssignUnitsToKingdomBuilding() {
        $gameKingdomBuilding = $this->createGameKingdomBuilding();

        $gameKingdomBuilding->update([
            'max_level' => 30
        ]);

        $unit = $this->createGameUnit();

        Livewire::test(Attributes::class, [
            'gameKingdomBuilding' => $gameKingdomBuilding->refresh(),
        ])->set('selectedUnits', [$unit->id])
          ->set('gameKingdomBuilding.units_per_level', 5)
          ->call('validateInput', 'nextStep', 2);

        $this->assertTrue($gameKingdomBuilding->refresh()->units->isNotEmpty());
    }
}
