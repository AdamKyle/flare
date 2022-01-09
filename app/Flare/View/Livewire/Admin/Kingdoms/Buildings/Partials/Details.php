<?php

namespace App\Flare\View\Livewire\Admin\Kingdoms\Buildings\Partials;

use App\Flare\Models\PassiveSkill;
use Livewire\Component;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameUnit;

class Details extends Component
{

    public $gameBuilding = null;

    public $editing      = false;

    public $passiveSkills;

    public $units;

    protected $rules = [
        'gameBuilding.name'                 => 'required',
        'gameBuilding.description'          => 'required',
        'gameBuilding.max_level'            => 'required',
        'gameBuilding.base_durability'      => 'required',
        'gameBuilding.base_defence'         => 'required',
        'gameBuilding.required_population'  => 'required',
        'gameBuilding.passive_skill_id'     => 'nullable',
        'gameBuilding.is_locked'            => 'nullable',
        'gameBuilding.level_required'       => 'nullable',
    ];

    protected $messages = [
        'gameBuilding.name'                 => 'Name is required.',
        'gameBuilding.description'          => 'Description is required.',
        'gameBuilding.max_level'            => 'Max Level is required.',
        'gameBuilding.base_durability'      => 'Base Durability is required.',
        'gameBuilding.base_defence'         => 'Base Defence is required.',
        'gameBuilding.required_population'  => 'Required Population is required.',
    ];

    protected $listeners = ['validateInput'];

    public function validateInput(string $functionName, int $index) {
        $this->validate();

        if (is_null($this->gameBuilding->is_locked)) {
            $this->gameBuilding->is_locked = false;
        }

        $this->gameBuilding->save();

        $this->emitTo('core.form-wizard', 'storeModel', $this->gameBuilding->refresh());
        $this->emitTo('core.form-wizard', $functionName, $index, true);
    }

    public function mount() {
        if (is_null($this->gameBuilding)) {
            $this->gameBuilding = new GameBuilding;
        }

        $this->passiveSkills = PassiveSkill::all();
        $this->units         = GameUnit::all();
    }

    public function render()
    {
        return view('components.livewire.admin.kingdoms.buildings.partials.details');
    }
}
