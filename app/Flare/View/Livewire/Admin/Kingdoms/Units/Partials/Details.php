<?php

namespace App\Flare\View\Livewire\Admin\Kingdoms\Units\Partials;

use Livewire\Component;
use App\Flare\Models\GameUnit;

class Details extends Component
{
    public $gameUnit     = null;

    public $editing      = false;

    public $weakAgainst  = false;

    public $units        = null;

    protected $rules = [
        'gameUnit.name'                     => 'required',
        'gameUnit.description'              => 'required',
        'gameUnit.attack'                   => 'required',
        'gameUnit.defence'                  => 'required',
        'gameUnit.can_heal'                 => 'nullable',
        'gameUnit.heal_amount'              => 'nullable',
        'gameUnit.siege_weapon'             => 'nullable',
        'gameUnit.travel_time'              => 'required',
        'gameUnit.wood_cost'                => 'required',
        'gameUnit.clay_cost'                => 'required',
        'gameUnit.stone_cost'               => 'required',
        'gameUnit.iron_cost'                => 'required',
        'gameUnit.required_population'      => 'required',
        'gameUnit.time_to_recruit'          => 'required',
        'gameUnit.weak_against_unit_id'     => 'nullable',
        'gameUnit.primary_target'           => 'nullable',
        'gameUnit.fall_back'                => 'nullable',
        'gameUnit.attacker'                 => 'nullable',
        'gameUnit.defender'                 => 'nullable',
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

        if (!is_null($this->gameUnit->weak_against_unit_id) && $this->weakAgainst) {
            return $this->addError('cant_be_both', 'Your unit cannot be weak against it\'s self and another unit.');
        }

        if (!is_null($this->gameUnit->primary_target) && !is_null($this->gameUnit->fall_back)) {
            if ($this->gameUnit->primary_target === $this->gameUnit->fall_back) {
                return $this->addError('error', 'Cannot have the same fallback target as the primary target.');
            }
        }

        if (is_null($this->gameUnit->siege_weapon)) {
            $this->gameUnit->siege_weapon = false;
        }

        $this->gameUnit->save();

        $gameUnit = $this->gameUnit->refresh();

        if (is_null($gameUnit->weak_against_unit_id) && !is_null($this->weakAgainst)) {
            $gameUnit->weak_against_unit_id = $gameUnit->id;

            $gameUnit->save();
        }

        $gameUnit = $gameUnit->refresh();

        $message = 'Created Unit: ' . $gameUnit->refresh()->name;

        if ($this->editing) {
            $message = 'Updated Unit: ' . $gameUnit->refresh()->name;
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
        } else {
            if ($this->gameUnit->weak_against_unit_id === $this->gameUnit->id) {
                $this->weakAgainst = true;
            }
        }

        $this->units = GameUnit::all();
    }

    public function render()
    {
        return view('components.livewire.admin.kingdoms.units.partials.details');
    }
}
