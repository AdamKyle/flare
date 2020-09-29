<?php

namespace App\Flare\View\Livewire\Admin\Monsters\Partials;

use App\Flare\Models\Monster;
use App\Flare\Values\BaseSkillValue;
use Livewire\Component;

class Stats extends Component
{
    protected $rules = [
        'monster.name'         => 'required',
        'monster.damage_stat'  => 'required',
        'monster.xp'           => 'required',
        'monster.str'          => 'required',
        'monster.dur'          => 'required',
        'monster.dex'          => 'required',
        'monster.chr'          => 'required',
        'monster.int'          => 'required',
        'monster.ac'           => 'required',
        'monster.gold'         => 'required',
        'monster.max_level'    => 'required',
        'monster.health_range' => 'required',
        'monster.attack_range' => 'required',
        'monster.drop_check'   => 'required',
    ];

    protected $listeners = ['validateInput'];

    protected $messages = [
        'monster.max_level.required'    => 'Max level must be set.',
        'monster.health_range.required' => 'Health range must be set.',
        'monster.attack_range.required' => 'Attack range must be set.',
        'monster.drop_check.required'   => 'Drop Check must be set.',
    ];

    public $monster;
    
    public function validateInput(string $functionName, int $index) {
        $this->validate();

        $this->monster->save();

        if ($this->monster->skills->isEmpty()) {
            // Get skills:
            foreach(config('game.skills') as $options) {
                if ($options['can_train']) {
                    $skills[] = resolve(BaseSkillValue::class)->getBaseMonsterSkillValue($this->monster, $options);
                }
            }

            // Set skills:
            $this->monster->skills()->insert($skills);
        }

        $this->emitTo('create', 'storeModel', $this->monster);
        $this->emitTo('create', $functionName, $index, true);
    }

    public function mount() {
        if (is_null($this->monster)) {
            $this->monster = new Monster;
        }

        if (is_array($this->monster)) {
            $this->monster = Monster::find($this->monster['id'])->load('skills');
        }
    }

    public function render() {
        return view('components.livewire.admin.monsters.partials.stats');
    }
}
