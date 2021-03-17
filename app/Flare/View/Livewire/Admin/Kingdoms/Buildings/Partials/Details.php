<?php

namespace App\Flare\View\Livewire\Admin\Kingdoms\Buildings\Partials;

use Livewire\Component;
use App\Flare\Models\GameKingdomBuilding;
use App\Flare\Models\GameUnit;

class Details extends Component
{

    public $gameKingdomBuilding = null;

    public $editing      = false;

    protected $rules = [
        'gameKingdomBuilding.name'                 => 'required',
        'gameKingdomBuilding.description'          => 'required',
        'gameKingdomBuilding.max_level'            => 'required',
        'gameKingdomBuilding.base_durability'      => 'required',
        'gameKingdomBuilding.base_defence'         => 'required',
        'gameKingdomBuilding.required_population'  => 'required',
    ];

    protected $messages = [
        'gameKingdomBuilding.name'                 => 'Name is required.',
        'gameKingdomBuilding.description'          => 'Description is required.',
        'gameKingdomBuilding.max_level'            => 'Max Level is required.',
        'gameKingdomBuilding.base_durability'      => 'Base Durability is required.',
        'gameKingdomBuilding.base_defence'         => 'Base Defence is required.',
        'gameKingdomBuilding.required_population'  => 'Required Population is required.',
    ];

    protected $listeners = ['validateInput'];

    public function validateInput(string $functionName, int $index) {
        $this->validate();

        $this->gameKingdomBuilding->save();

        $this->emitTo('core.form-wizard', 'storeModel', $this->gameKingdomBuilding->refresh());
        $this->emitTo('core.form-wizard', $functionName, $index, true);
    }

    public function mount() {
        if (is_null($this->gameKingdomBuilding)) {
            $this->gameKingdomBuilding = new GameKingdomBuilding;
        }

        $this->units = GameUnit::all();
    }

    public function render()
    {
        return view('components.livewire.admin.kingdoms.buildings.partials.details');
    }
}
