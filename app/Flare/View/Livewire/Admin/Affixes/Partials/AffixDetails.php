<?php

namespace App\Flare\View\Livewire\Admin\Affixes\Partials;

use App\Flare\Models\GameSkill;
use App\Flare\Models\ItemAffix;
use Livewire\Component;

class AffixDetails extends Component
{

    public $itemAffix;

    public $types = [
        'suffix',
        'prefix'
    ];

    protected $rules = [
        'itemAffix.name'                 => 'required',
        'itemAffix.type'                 => 'required',
        'itemAffix.description'          => 'required',
        'itemAffix.cost'                 => 'required',
        'itemAffix.int_required'         => 'required',
        'itemAffix.skill_level_required' => 'required',
        'itemAffix.skill_level_trivial'  => 'required',
        'itemAffix.can_drop'             => 'nullable',
    ];

    protected $messages = [
        'itemAffix.name.required'        => 'Name is required.',
        'itemAffix.type.required'        => 'Type is required.',
        'itemAffix.description.required' => 'Description is required.',
        'itemAffix.cost.required'        => 'Cost is required.',
        'itemAffix.int_required'         => 'Intelligence Required is required',
        'itemAffix.skill_level_required' => 'Skill Level Required is required',
        'itemAffix.skill_level_trivial'  => 'Skill Level Trivial is required',
    ];

    protected $listeners = ['validateInput'];

    public function validateInput(string $functionName, int $index) {
        $this->validate();

        if (is_null($this->itemAffix->can_drop)) {
            $this->itemAffix->can_drop = false;
        }

        $this->itemAffix->save();

        $this->emitTo('core.form-wizard', 'storeModel', $this->itemAffix->refresh());
        $this->emitTo('core.form-wizard', $functionName, $index, true);
    }

    public function mount() {

        if (is_null($this->itemAffix)) {
            $this->itemAffix = new ItemAffix;
        }
    }

    public function render()
    {
        return view('components.livewire.admin.affixes.partials.affix-details');
    }
}
