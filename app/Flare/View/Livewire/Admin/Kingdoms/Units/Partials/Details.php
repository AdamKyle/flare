<?php

namespace App\Flare\View\Livewire\Admin\Kingdoms\Units\Partials;

use Livewire\Component;
use App\Flare\Models\GameUnit;

class Details extends Component
{
    public $gameUnit = null;

    public $editing  = false;

    protected $rules = [
        'gameUnit.name'                => 'required',
        'gameUnit.description'         => 'required',
        'gameUnit.attack'              => 'required',
        'gameUnit.defence'             => 'required',
        'gameUnit.can_heal'            => 'nullable',
        'gameUnit.heal_amount'         => 'nullable',
        'gameUnit.siege_weapon'        => 'nullable',
        'gameUnit.travel_time'         => 'required',
        'gameUnit.wood_cost'           => 'nullable',
        'gameUnit.clay_cost'           => 'nullable',
        'gameUnit.stone_cost'          => 'nullable',
        'gameUnit.iron_cost'           => 'nullable',
        'gameUnit.required_population' => 'required',
        'gameUnit.time_to_recruit'     => 'required',
    ];

    protected $messages = [
        'gameUnit.name'                => 'Name is required.',
        'gameUnit.description'         => 'Description is required.',
        'gameUnit.attack'              => 'Attack is required.',
        'gameUnit.defence'             => 'Defence is required.',
        'gameUnit.travel_time'         => 'How long does it take this unit to travel?',
        'gameUnit.required_population' => 'How many people does it take to recruit this unit?',
        'gameUnit.time_to_recruit'     => 'How long does it take to recruit one of these units?',
    ];

    protected $listeners = ['validateInput'];

    public function validateInput(string $functionName, int $index) {
        $this->validate();

        $this->gameUnit->save();

        $message = 'Created Unit: ' . $this->gameUnit->refresh()->name;

        if ($this->editing) {
            $message = 'Updated Unit: ' . $this->gameUnit->refresh()->name;
        }
        
        $this->emitTo('core.form-wizard', $functionName, $index, true, [
            'type'    => 'success',
            'message' => $message,
        ]);
    }

    public function getIsHealForDisabledProperty() {
        return !$this->gameUnit->can_heal;
    }

    public function mount() {
        if (is_null($this->gameUnit)) {
            $this->gameUnit = new GameUnit;
        }
    }

    public function render()
    {
        return view('components.livewire.admin.kingdoms.units.partials.details');
    }
}
