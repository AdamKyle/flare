<?php

namespace App\Flare\View\Livewire\Admin\Monsters\Partials;

use App\Flare\Models\Monster;
use App\Flare\Values\BaseSkillValue;
use Livewire\Component;

class Stats extends Component
{
    protected $rules     = [
        'name'         => 'required',
        'damage_stat'  => 'required',
        'xp'           => 'required',
        'str'          => 'required',
        'dur'          => 'required',
        'dex'          => 'required',
        'chr'          => 'required',
        'int'          => 'required',
        'ac'           => 'required',
        'gold'         => 'required',
        'max_level'    => 'required',
        'health_range' => 'required',
        'attack_range' => 'required',
        'drop_check'   => 'required',
    ];

    protected $listeners = ['validateInput'];

    public $monster = null;

    public $name          = '';
    public $damage_stat   = '';
    public $xp            = '';
    public $str           = '';
    public $dur           = '';
    public $dex           = '';
    public $chr           = '';
    public $int           = '';
    public $ac            = '';
    public $gold          = '';
    public $max_level     = '';
    public $health_range  = '';
    public $attack_range  = '';
    public $drop_check    = '';
    

    public function validateInput(string $functionName, int $index) {
        $data = $this->validate();

        if ($data) {
            if (!is_null($this->monster)) {

                // if the monster is an array of attributes, find the monster based on it's id:
                if (is_array($this->monster)) {
                    $this->monster = Monster::find($this->monster['id']);
                }
                
                $this->monster->update($data);
            } else {
                // Create the monster:
                $this->monster = Monster::create($data);

                // Get skills:
                foreach(config('game.skills') as $options) {
                    $skills[] = resolve(BaseSkillValue::class)->getBaseMonsterSkillValue($this->monster, $options);
                }
                
                // Set skills:
                $this->monster->skills()->insert($skills);
            }

            // Refresh the monster:
            $this->monster = $this->monster->refresh()->load('skills');

            // Pass it along:
            // This will come through as an array as the model gets turned to json
            // for event emition.
            $this->emitTo('create', 'storeMonster', $this->monster);
            $this->emitTo('create', $functionName, $index, true);
        }
    }

    public function mount() {
        if (!is_null($this->monster)) {
            foreach ($this->monster as $attribute => $value) {
                if (!is_array($value)) {
                    $this->{$attribute} = $value;
                }
            }
        }
    }

    public function render() {
        return view('components.livewire.admin.monsters.partials.stats');
    }
}
