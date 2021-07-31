<?php

namespace App\Flare\View\Livewire\Admin\Skills\Partials;

use App\Flare\Models\GameSkill;
use App\Game\Skills\Values\SkillTypeValue;
use Livewire\Component;

class SkillDetails extends Component
{
    public $skill;

    public $skillTypes;

    protected $rules = [
        'skill.name'                    => 'required',
        'skill.type'                    => 'required',
        'skill.description'             => 'required',
        'skill.max_level'               => 'required',
        'skill.can_monsters_have_skill' => 'nullable',
        'skill.is_locked'               => 'nullable',
    ];

    protected $messages =[
        'skill.name.required'        => 'Name required.',
        'skill.name.type'            => 'Type required.',
        'skill.description.required' => 'Description required.',
        'skill.max_level.required'   => 'Max Level is required.',
    ];

    protected $listeners = ['validateInput'];

    public $types = [
        'spell-damage',
        'spell-healing',
    ];

    public function validateInput(string $functionName, int $index) {
        if (is_null($this->skill->can_monsters_have_skill)) {
            $this->skill->can_monsters_have_skill = false;
        }

        if (is_null($this->skill->is_locked)) {
            $this->skill->is_locked = false;
        }

        $this->validate();

        if ($this->skill->max_level <= 0) {
            $this->addError('gameSkill.max_level', 'Cannot be equal to or less then 0');
        }

        $this->skill->save();

        $this->emitTo('core.form-wizard', 'storeModel', $this->skill->refresh());
        $this->emitTo('core.form-wizard', $functionName, $index, true);
    }

    public function mount() {
        if (is_null($this->skill)) {
            $this->skill = new GameSkill;
        }

        $this->skillTypes = SkillTypeValue::$namedValues;
    }

    public function render() {
        return view('components.livewire.admin.skills.partials.skill-details');
    }
}
