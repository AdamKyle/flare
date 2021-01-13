<?php

namespace App\Flare\View\Livewire\Admin\Kingdoms\Buildings\Partials;

use App\Admin\Services\UpdateKingdomsService;
use App\Flare\Models\GameBuilding;
use Livewire\Component;

class Attributes extends Component
{
    public $gameBuilding;

    public $editing;

    protected $rules = [
        'gameBuilding.is_walls'                   => 'nullable',
        'gameBuilding.is_church'                  => 'nullable',
        'gameBuilding.is_farm'                    => 'nullable',
        'gameBuilding.is_resource_building'       => 'nullable',
        'gameBuilding.trains_units'               => 'nullable',
        'gameBuilding.wood_cost'                  => 'nullable',
        'gameBuilding.clay_cost'                  => 'nullable',
        'gameBuilding.stone_cost'                 => 'nullable',
        'gameBuilding.iron_cost'                  => 'nullable',
        'gameBuilding.increase_population_amount' => 'nullable',
        'gameBuilding.increase_morale_amount'     => 'nullable',
        'gameBuilding.decrease_morale_amount'     => 'nullable',
        'gameBuilding.increase_wood_amount'       => 'nullable',
        'gameBuilding.increase_clay_amount'       => 'nullable',
        'gameBuilding.increase_stone_amount'      => 'nullable',
        'gameBuilding.increase_iron_amount'       => 'nullable',
        'gameBuilding.increase_durability_amount' => 'nullable',
        'gameBuilding.increase_defence_amount'    => 'nullable',
        'gameBuilding.time_to_build'              => 'nullable',
        'gameBuilding.time_increase_amount'       => 'nullable',
    ];

    protected $listeners = ['validateInput', 'update'];

    public function mount() {
        if (is_array($this->gameBuilding)) {
            $this->gameBuilding = GameBuilding::find($this->gameBuilding['id']);
        }
    }

    public function update($id) {
        $this->gameBuilding = GameBuilding::find($id);
    }

    public function validateInput(string $functionName, int $index) {
        $this->validate();

        $this->gameBuilding->save();

        (new UpdateKingdomsService)->updateKingdomBuildings($this->gameBuilding->refresh());

        $message = 'Created Building: ' . $this->gameBuilding->refresh()->name;

        if ($this->editing) {
            $message = 'Updated Building: ' . $this->gameBuilding->refresh()->name;
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
}
