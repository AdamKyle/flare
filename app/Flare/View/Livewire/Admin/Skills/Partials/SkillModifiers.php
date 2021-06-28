<?php

namespace App\Flare\View\Livewire\Admin\Skills\Partials;

use App\Admin\Jobs\AssignSkillsJob;
use App\Flare\Models\Character;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Monster;
use Illuminate\Support\Collection;
use Livewire\Component;

class SkillModifiers extends Component
{
    public $skill;

    public $for = '';

    public $monsters;

    public $gameClasses;

    public $selectedMonsters = [];

    public $selectedClass = null;

    public $canNotAssignSkill;

    public $editing = false;

    protected $rules = [
        'skill.base_damage_mod_bonus_per_level'    => 'nullable',
        'skill.base_healing_mod_bonus_per_level'   => 'nullable',
        'skill.base_ac_mod_bonus_per_level'        => 'nullable',
        'skill.fight_time_out_mod_bonus_per_level' => 'nullable',
        'skill.move_time_out_mod_bonus_per_level'  => 'nullable',
        'skill.can_train'                          => 'nullable',
        'skill.skill_bonus_per_level'              => 'nullable',
    ];

    protected $listeners = ['validateInput', 'update'];

    public function validateInput(string $functionName, int $index) {
        $this->validate();

        if (is_null($this->skill->can_train)) {
            $this->skill->can_train = false;
        }

        if ($this->isMissing()) {
            $this->addError('error', 'You must supply some kind of bonus per level.');
        } else if ($this->isBelowZero()) {
            $this->addError('error', 'No bonus may be below  or equal to: 0.');
        } else if ($this->for === 'select-monsters' && empty($this->selectedMonsters)) {
            $this->addError('monster', 'At least one or more monsters must be selected.');
        } else if ($this->for === 'select-class' && (is_null($this->selectedClass) || $this->selectedClass === '')) {
            $this->addError('class', 'Class must be selected.');
        } else {
            $this->skill->save();

            if (!$this->canNotAssignSkill) {

                if (empty($this->selectedMonsters)) {
                    AssignSkillsJob::dispatch($this->for, $this->skill->refresh(), auth()->user(), null, $this->selectedClass);
                } else {
                    foreach ($this->selectedMonsters as $monsterId) {
                        AssignSkillsJob::dispatch($this->for, $this->skill->refresh(), auth()->user(), $monsterId);
                    }
                }


            }

            $message = 'Skill: ' . $this->skill->name . ' Created. Applying to selected entities!';

            if ($this->editing) {
                $message = 'Skill: ' . $this->skill->name . ' Updated. Applying to selected entities!';
            }

            $this->emitTo('core.form-wizard', 'finish', $index, true, [
                'type'    => 'success',
                'message' => $message,
            ]);
        }
    }

    public function update($id) {
        $this->skill             = GameSkill::find($id);
        $this->for               = $this->forValue();
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
        $this->monsters    = Monster::all();
        $this->gameClasses = GameClass::all();
    }


    public function render()
    {
        return view('components.livewire.admin.skills.partials.skill-modifiers');
    }

    protected function forValue() : string {
        $for = '';

        if (is_null($this->skill)) {
            return $for;
        }

        $monstersWithSkill = Monster::join('skills', function($join) {
            $join->on('skills.monster_id', 'monsters.id')
                 ->where('skills.game_skill_id', $this->skill->id);
        })->select('monsters.*')->get();

        $charactersWithSkill = Character::join('skills', function($join) {
            $join->on('skills.character_id', 'characters.id')
                 ->where('skills.game_skill_id', $this->skill->id);
        })->select('characters.*')->get();

        if ($monstersWithSkill->isNotEmpty() && $charactersWithSkill->isNotEmpty()) {
            $for = 'all';

            $this->canNotAssignSkill = true;
        }

        if ($monstersWithSkill->isNotEmpty() && $charactersWithSkill->isEmpty()) {
            $for = 'select-monsters';

            $this->selectedMonsters = $monstersWithSkill->pluck('id')->toArray();

            $this->canNotAssignSkill = true;
        }

        if ($charactersWithSkill->isNotEmpty()) {
            $for = 'selected-class';

            $this->selectedClass = $charactersWithSkill->first()->class->id;

            $this->canNotAssignSkill = true;
        }

        return $for;
    }
}
