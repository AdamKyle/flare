<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Kingdom\Buildings\Partials;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Kingdoms\Buildings\Partials\Attributes;
use App\Flare\Models\GameBuilding;
use Tests\TestCase;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateGameUnit;

class AtrributesTest extends TestCase
{
    use RefreshDatabase, CreateGameBuilding, CreateGameUnit;

    public function testTheComponentLoads() {
        Livewire::test(Attributes::class, [
            'gameBuilding' => $this->createGameBuilding()->toArray(),
        ])->assertSee('Cost in Wood:');
    }

    public function testTheComponentLoadsWithUnitSelectionDisabled() {
        $gameBuilding = $this->createGameBuilding();

        $gameBuilding->update([
            'trains_units' => true,
        ]);

        Livewire::test(Attributes::class, [
            'gameBuilding' => $gameBuilding->refresh()
        ])->assertSee('Cost in Wood:');
    }

    public function testTheComponentLoadsWithUnitSelectionNotDisabled() {
        $gameBuilding = $this->createGameBuilding();

        $gameBuilding->update([
            'trains_units' => true,
        ]);

        $gameBuilding->units()->create([
            'game_building_id' => $gameBuilding->id,
            'game_unit_id'     => $this->createGameUnit()->id,
            'required_level'   => 1,
        ]);

        Livewire::test(Attributes::class, [
            'gameBuilding' => $gameBuilding->refresh()
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

    public function testSelectedUnitsWithNoPerLevelValidationError() {
        $unit = $this->createGameUnit();

        Livewire::test(Attributes::class, [
            'gameBuilding' => $this->createGameBuilding(),
        ])->set('selectedUnits', [$unit->id])->call('validateInput', 'nextStep', 2)->assertSee('How many levels between units?');
    }

    public function testSelectedUnitsValidationFails() {
        $unit = $this->createGameUnit();

        Livewire::test(Attributes::class, [
            'gameBuilding' => $this->createGameBuilding(),
        ])->set('selectedUnits', [$unit->id])
          ->set('gameBuilding.units_per_level', 300)
          ->call('validateInput', 'nextStep', 2)
          ->assertSee('Your selected units and units per level are greator then your max level.');
    }

    public function testIsEditing() {
        Livewire::test(Attributes::class, [
            'gameBuilding' => $this->createGameBuilding(),
            'editing'      => true,
        ])->call('validateInput', 'nextStep', 2)
          ->assertSet('editing', true);
    }

    public function testAssignUnitsToKingdomBuilding() {
        $gameBuilding = $this->createGameBuilding();

        $gameBuilding->update([
            'max_level' => 30
        ]);

        $unitIds = $this->createGameUnits([], 3)->pluck('id')->toArray();

        Livewire::test(Attributes::class, [
            'gameBuilding' => $gameBuilding->refresh(),
        ])->set('selectedUnits', $unitIds)
          ->set('gameBuilding.units_per_level', 5)
          ->call('validateInput', 'nextStep', 2);

        $this->assertTrue($gameBuilding->refresh()->units->isNotEmpty());
    }
}
