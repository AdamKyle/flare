<?php

namespace App\Flare\View\Livewire\Admin\Monsters\Partials;

use App\Flare\Models\Monster;
use App\Flare\Models\Skill;
use Livewire\Component;

class Skills extends Component
{

    public $monster       = null;

    public $selectedSkill = null;

    public $monsterSkill  = null;
    
    protected $rules = [
        'monsterSkill.level' => 'required',
    ];

    protected $listeners = ['validateInput', 'update'];

    public function update($id) {
        $this->monster = Monster::find($id)->load('questItem');
    }

    public function updatedSelectedSkill() {
        $this->monsterSkill = null;
    }

    public function validateInput(string $functionName, int $index) {
        // This page is optional:
        if (!is_null($this->selectedSkill)) {
            $this->validate();

            $this->monsterSkill->save();
        }

        $this->emitTo('core.form-wizard', 'storeModel', $this->monster->refresh()->load('skills'));
        $this->emitTo('core.form-wizard', $functionName, $index, true);
    }

    public function editSkill() {
        if (is_null($this->selectedSkill)) {
            $this->addError('skill', 'No skill selected.');
        } else {
            $this->monsterSkill = $this->monster->skills->where('id', $this->selectedSkill)->first();
        }
    }

    public function save() {
        $this->validate();

        $this->monsterSkill->save();

        session()->flash('message', 'Saved successfully.');

        $this->emitTo('core.form.wizard', 'storeMonster', $this->monster->refresh()->load('skills'), true, 'admin.monsters.partials.skills');
    }

    public function render()
    {
        return view('components.livewire.admin.monsters.partials.skills');
    }
}
