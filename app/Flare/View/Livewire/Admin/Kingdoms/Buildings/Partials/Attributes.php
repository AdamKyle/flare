<?php

namespace App\Flare\View\Livewire\Admin\Kingdoms\Buildings\Partials;

use App\Admin\Services\UpdateKingdomsService;
use App\Flare\Models\GameKingdomBuilding;
use App\Flare\Models\GameUnit;
use Livewire\Component;

class Attributes extends Component
{
    public $gameKingdomBuilding;

    public $gameUnits;

    public $editing = false;

    public $selectedUnits = [];

    protected $rules = [
        'gameKingdomBuilding.is_walls'                   => 'nullable',
        'gameKingdomBuilding.is_church'                  => 'nullable',
        'gameKingdomBuilding.is_farm'                    => 'nullable',
        'gameKingdomBuilding.is_resource_building'       => 'nullable',
        'gameKingdomBuilding.trains_units'               => 'nullable',
        'gameKingdomBuilding.wood_cost'                  => 'nullable',
        'gameKingdomBuilding.clay_cost'                  => 'nullable',
        'gameKingdomBuilding.stone_cost'                 => 'nullable',
        'gameKingdomBuilding.iron_cost'                  => 'nullable',
        'gameKingdomBuilding.increase_population_amount' => 'nullable',
        'gameKingdomBuilding.increase_morale_amount'     => 'nullable',
        'gameKingdomBuilding.decrease_morale_amount'     => 'nullable',
        'gameKingdomBuilding.increase_wood_amount'       => 'nullable',
        'gameKingdomBuilding.increase_clay_amount'       => 'nullable',
        'gameKingdomBuilding.increase_stone_amount'      => 'nullable',
        'gameKingdomBuilding.increase_iron_amount'       => 'nullable',
        'gameKingdomBuilding.increase_durability_amount' => 'nullable',
        'gameKingdomBuilding.increase_defence_amount'    => 'nullable',
        'gameKingdomBuilding.time_to_build'              => 'nullable',
        'gameKingdomBuilding.time_increase_amount'       => 'nullable',
        'gameKingdomBuilding.units_per_level'            => 'nullable',
    ];

    protected $listeners = ['validateInput', 'update'];

    public function getUnitSelectionIsDisabledProperty() {
        if (is_null($this->gameKingdomBuilding)) {
            return true;
        }

        if (!$this->gameKingdomBuilding->trains_units) {
            return true;
        }

        if ($this->gameUnits->isEmpty()) {
            return true;
        }

        return false;
    }

    public function mount() {
        if (is_array($this->gameKingdomBuilding)) {
            $this->gameKingdomBuilding = GameKingdomBuilding::find($this->gameKingdomBuilding['id']);
        }

        $this->gameUnits = GameUnit::all();
    }

    public function update($id) {
        $this->gameKingdomBuilding = GameKingdomBuilding::find($id);

        $this->selectedUnits = $this->gameKingdomBuilding->units()->pluck('game_unit_id')->toArray();
    }

    public function validateInput(string $functionName, int $index) {
        $this->validate();

        $isValid = $this->validateSelectedUnits();

        if (!empty($this->selectedUnits) && is_null($this->gameKingdomBuilding->units_per_level)) {
            return $this->addError('units_per_level', 'How many levels between units?');
        }

        if (!$isValid) {
            return $this->addError('error', 'Your selected units and units per level are greator then your max level.');
        }

        $this->gameKingdomBuilding->save();

        $gameKingdomBuilding = $this->gameKingdomBuilding->refresh();

        $kingdomService = new UpdateKingdomsService();

        $kingdomService->updateKingdomKingdomBuildings($this->gameKingdomBuilding->refresh(), $this->selectedUnits, $gameKingdomBuilding->units_per_level);

        $message = 'Created KingdomBuilding: ' . $this->gameKingdomBuilding->refresh()->name;

        if ($this->editing) {
            $message = 'Updated KingdomBuilding: ' . $this->gameKingdomBuilding->refresh()->name;
        }
        
        $this->emitTo('core.form-wizard', $functionName, $index, true, [
            'type'    => 'success',
            'message' => $message,
        ]);
    }

    public function render()
    {
        return view('components.livewire.admin.kingdoms.buildings.partials.attributes');
    }

    protected function validateSelectedUnits() {
        $total = (count($this->selectedUnits) * $this->gameKingdomBuilding->units_per_level) - (count($this->selectedUnits) - 1);

        if ($total > $this->gameKingdomBuilding->max_level) {
            return false;
        }

        return true;
    }
}
