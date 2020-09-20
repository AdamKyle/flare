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
        'monsterSkill.base_damage_mod'  => 'required',
        'monsterSkill.base_healing_mod' => 'required',
        'monsterSkill.base_ac_mod'      => 'required',
    ];

    protected $listeners = ['validateInput'];

    public function mount() {
        if (!is_null($this->monster)) {
            if (is_array($this->monster)) {
                $this->monster = Monster::find($this->monster['id'])->load('skills');
            }
        }
    }

    public function validateInput(string $functionName, int $index) {
        // This page is optional:
        if (!is_null($this->selectedSkill)) {
            $this->validate();

            $this->monsterSkill->save();
        }

        $this->emitTo('create', 'storeMonster', $this->monster->refresh()->load('skills'));
        $this->emitTo('create', $functionName, $index, true);
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

        $this->emitTo('create', 'storeMonster', $this->monster->refresh()->load('skills'));
    }

    public function render()
    {
        return view('components.livewire.admin.monsters.partials.skills');
    }
}
