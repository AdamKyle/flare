<?php

namespace App\Flare\View\Livewire\Admin\Affixes\Partials;

use App\Flare\Models\ItemAffix;
use Livewire\Component;

class AffixModifier extends Component
{

    public $itemAffix;

    protected $listeners = ['validateInput'];

    protected $rules = [
        'itemAffix.base_damage_mod'      => 'nullable',
        'itemAffix.base_ac_mod'          => 'nullable',
        'itemAffix.base_healing_mod'     => 'nullable',
        'itemAffix.str_mod'              => 'nullable',
        'itemAffix.dur_mod'              => 'nullable',
        'itemAffix.dex_mod'              => 'nullable',
        'itemAffix.chr_mod'              => 'nullable',
        'itemAffix.int_mod'              => 'nullable',
        'itemAffix.skill_name'           => 'nullable',
        'itemAffix.skill_training_bonus' => 'nullable',
    ];
    
    public function mount() {
        if (is_array($this->itemAffix)) {
            $this->itemAffix = ItemAffix::find($this->itemAffix['id']);
        }
    }

    public function validateInput(string $functionName, int $index) {
        $this->validate();

        if (!is_null($this->itemAffix->skill_name) && is_null($this->itemAffix->skill_training_bonus)) {
            $this->addError('skill_training_bonus', 'Must have a valid value since you selected a skill');
        } else {
            $this->itemAffix->save();

            $this->emitTo('manage', 'storeModel', $this->itemAffix->refresh());
            $this->emitTo('manage', $functionName, $index, true);
        }
    }

    public function render()
    {
        return view('components.livewire.admin.affixes.partials.affix-modifier');
    }
}
