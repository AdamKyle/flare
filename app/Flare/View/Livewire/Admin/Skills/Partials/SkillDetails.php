<?php

namespace App\Flare\View\Livewire\Admin\Skills\Partials;

use App\Flare\Models\GameSkill;
use Livewire\Component;

class SkillDetails extends Component
{
    public $skill;

    protected $rules = [
        'skill.name'        => 'required',
        'skill.description' => 'required',
        'skill.max_level'   => 'required',
    ];

    protected $messages =[
        'skill.name.required'        => 'Name required.',
        'skill.description.required' => 'Description required.',
        'skill.max_level.required'   => 'Max Level is required.'
    ];

    protected $listeners = ['validateInput'];

    public function validateInput(string $functionName, int $index) {
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
    }

    public function render() {
        return view('components.livewire.admin.skills.partials.skill-details');
    }
}
