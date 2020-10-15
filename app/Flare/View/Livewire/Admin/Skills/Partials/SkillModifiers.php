<?php

namespace App\Flare\View\Livewire\Admin\Skills\Partials;

use App\Admin\Jobs\AssignSkillsJob;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Monster;
use Livewire\Component;

class SkillModifiers extends Component
{
    public $skill;

    public $for = '';

    public $monsters;

    public $monster;

    public $canNotAssignSkill;

    protected $rules = [
        'skill.base_damage_mod_bonus_per_level'    => 'nullable',
        'skill.base_healing_mod_bonus_per_level'   => 'nullable',
        'skill.base_ac_mod_bonus_per_level'        => 'nullable',
        'skill.fight_time_out_mod_bonus_per_level' => 'nullable',
        'skill.move_time_out_mod_bonus_per_level'  => 'nullable',
        'skill.can_train'                          => 'nullable',
        'skill.skill_bonus_per_level'              => 'nullable',
    ];

    protected $listeners = ['validateInput'];

    public function validateInput(string $functionName, int $index) {
        $this->validate();

        if ($this->isMissing()) {
            $this->addError('error', 'You must supply some kind of bonus per level.');
        } else if ($this->isBelowZero()) {
            $this->addError('error', 'No bonus may be below  or equal to: 0.');
        } else if ($this->for === 'selected-monster' && is_null($this->monster)) {
            $this->addError('monster', 'Monster must be selected.');
        } else {
            $this->skill->save();

            if (!$this->canNotAssignSkill) {
                AssignSkillsJob::dispatch($this->for, $this->skill->refresh(), auth()->user(), $this->monster);
            }

            $this->emitTo('manage', 'redirectSessionMessage', 'success', 'Skill: ' . $this->skill->name . ' Created. Applying to selected entities!');

            $this->emitTo('manage', 'storeModel', $this->skill->refresh());
            $this->emitTo('manage', $functionName, $index, true);
        }
    }

    public function isMissing(): Bool {
        return is_null($this->skill->base_damage_mod_bonus_per_level) &&
               is_null($this->skill->base_healing_mod_bonus_per_level) &&
               is_null($this->skill->base_ac_mod_bonus_per_level) &&
               is_null($this->skill->fight_time_out_mod_bonus_per_level) &&
               is_null($this->skill->move_time_out_mod_bonus_per_level) &&
               is_null($this->skill->skill_bonus_per_level);
    }

    public function isBelowZero(): Bool {
        return $this->skill->base_damage_mod_bonus_per_level <= 0 &&
               $this->skill->base_healing_mod_bonus_per_level <= 0 &&
               $this->skill->base_ac_mod_bonus_per_level <= 0 &&
               $this->skill->fight_time_out_mod_bonus_per_level <= 0 &&
               $this->skill->move_time_out_mod_bonus_per_level <= 0 &&
               $this->skill->skill_bonus_per_level <= 0;
    }

    public function mount() {
        if (is_array($this->skill)) {
            $this->skill = GameSkill::find($this->skill['id']);
        }

        $this->monsters = Monster::all();

        $this->canNotAssignSkill = $this->canNotAssignSkill();
    }
    

    public function render()
    {
        return view('components.livewire.admin.skills.partials.skill-modifiers');
    }

    protected function canNotAssignSkill(): Bool {
        $monstersWithSkill = Monster::join('skills', function($join) {
            $join->on('skills.monster_id', 'monsters.id')
                 ->where('skills.game_skill_id', $this->skill->id);
        })->get();

        $charactersWithSkill = Character::join('skills', function($join) {
            $join->on('skills.character_id', 'characters.id')
                 ->where('skills.game_skill_id', $this->skill->id);
        })->get();

        return $monstersWithSkill->isNotEmpty() || $charactersWithSkill->isNotEmpty();
    }
}
