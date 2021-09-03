<?php

namespace App\Flare\View\Livewire\Admin\Monsters\Partials;

use App\Flare\Models\GameMap;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Monster;
use App\Flare\Values\BaseSkillValue;
use Livewire\Component;

class Stats extends Component
{

    public $gameMaps;

    protected $rules = [
        'monster.name'                => 'required',
        'monster.damage_stat'         => 'required',
        'monster.xp'                  => 'required',
        'monster.str'                 => 'required',
        'monster.dur'                 => 'required',
        'monster.dex'                 => 'required',
        'monster.chr'                 => 'required',
        'monster.int'                 => 'required',
        'monster.agi'                 => 'required',
        'monster.focus'               => 'required',
        'monster.ac'                  => 'required',
        'monster.gold'                => 'required',
        'monster.max_level'           => 'required',
        'monster.health_range'        => 'required',
        'monster.attack_range'        => 'required',
        'monster.drop_check'          => 'required',
        'monster.game_map_id'         => 'required',
        'monster.is_celestial_entity' => 'nullable',
        'monster.can_cast'            => 'nullable',
        'monster.gold_cost'           => 'nullable',
        'monster.gold_dust_cost'      => 'nullable',
        'monster.can_use_artifacts'   => 'nullable',
        'monster.max_spell_damage'    => 'nullable',
        'monster.max_artifact_damage' => 'nullable',
        'monster.shards'              => 'nullable',
        'monster.spell_evasion'       => 'nullable',
        'monster.artifact_annulment'  => 'nullable',
    ];

    protected $listeners = ['validateInput'];

    protected $messages = [
        'monster.max_level.required'    => 'Max level must be set.',
        'monster.health_range.required' => 'Health range must be set.',
        'monster.attack_range.required' => 'Attack range must be set.',
        'monster.drop_check.required'   => 'Drop Check must be set.',
        'monster.damage_stat.required'  => 'Damage stat is missing',
        'monster.game_map_id.required'  => 'What map is this monster for?',
    ];

    public $monster;

    public function validateInput(string $functionName, int $index) {
        $this->validate();

        if (is_null($this->monster->published)) {
            $this->monster->published = false;
        }

        if (is_null($this->monster->is_celestial_entity)) {
            $this->monster->is_celestial_entity = false;
            $this->monster->gold_cost = 0;
            $this->monster->gold_dust_cost = 0;
            $this->monster->shards = 0;
        }

        if (is_null($this->monster->can_cast)) {
            $this->monster->can_cast = false;

            $this->monster->max_spell_damage = 0;
        }

        if (is_null($this->monster->can_use_artifacts)) {
            $this->monster->can_use_artifacts = false;

            $this->monster->max_artifact_damage = 0;
        }

        $this->monster->save();

        if ($this->monster->skills->isEmpty()) {
            $skills = [];

            // Get skills:
            foreach(GameSkill::all() as $skill) {
                if ($skill->can_train && $skill->can_monsters_have_skill) {
                    $skills[] = resolve(BaseSkillValue::class)->getBaseMonsterSkillValue($this->monster, $skill);
                }
            }

            // Set skills:
            $this->monster->skills()->insert($skills);
        }

        $this->emitTo('core.form-wizard', 'storeModel', $this->monster);
        $this->emitTo('core.form-wizard', $functionName, $index, true);
    }

    public function mount() {
        if (is_null($this->monster)) {
            $this->monster = new Monster;
        }

        if (is_array($this->monster)) {
            $this->monster = Monster::find($this->monster['id'])->load('skills');
        }

        $this->gameMaps = GameMap::all();
    }

    public function render() {
        return view('components.livewire.admin.monsters.partials.stats');
    }

}
