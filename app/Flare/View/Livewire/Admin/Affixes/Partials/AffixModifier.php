<?php

namespace App\Flare\View\Livewire\Admin\Affixes\Partials;

use App\Flare\Models\GameSkill;
use App\Flare\Models\ItemAffix;
use Livewire\Component;

class AffixModifier extends Component
{

    public $itemAffix;

    public $skills;

    public $editing = false;

    protected $listeners = ['validateInput' , 'update'];

    protected $rules = [
        'itemAffix.base_damage_mod'          => 'nullable',
        'itemAffix.base_ac_mod'              => 'nullable',
        'itemAffix.base_healing_mod'         => 'nullable',
        'itemAffix.str_mod'                  => 'nullable',
        'itemAffix.dur_mod'                  => 'nullable',
        'itemAffix.dex_mod'                  => 'nullable',
        'itemAffix.chr_mod'                  => 'nullable',
        'itemAffix.int_mod'                  => 'nullable',
        'itemAffix.agi_mod'                  => 'nullable',
        'itemAffix.focus_mod'                => 'nullable',
        'itemAffix.skill_name'               => 'nullable',
        'itemAffix.skill_bonus'              => 'nullable',
        'itemAffix.skill_training_bonus'     => 'nullable',
        'itemAffix.base_damage_mod_bonus'    => 'nullable',
        'itemAffix.base_healing_mod_bonus'   => 'nullable',
        'itemAffix.base_ac_mod_bonus'        => 'nullable',
        'itemAffix.fight_time_out_mod_bonus' => 'nullable',
        'itemAffix.move_time_out_mod_bonus'  => 'nullable',
    ];

    public function update($id) {
        $this->itemAffix = ItemAffix::find($id);
    }

    public function mount() {
        $this->skills = GameSkill::all();
    }

    public function validateInput(string $functionName, int $index) {
        $this->validate();

        if (!is_null($this->itemAffix->skill_name) && is_null($this->itemAffix->skill_training_bonus)) {
            $this->addError('skill_training_bonus', 'Must have a valid value since you selected a skill');
        } else {
            $this->itemAffix->save();

            $message = 'Created Affix: ' . $this->itemAffix->refresh()->name;

            if ($this->editing) {
                $message = 'Updated Affix: ' . $this->itemAffix->refresh()->name;
            }

            $this->emitTo('core.form-wizard', $functionName, $index, true, [
                'type'    => 'success',
                'message' => $message,
            ]);
        }
    }

    public function render()
    {
        return view('components.livewire.admin.affixes.partials.affix-modifier');
    }
}
