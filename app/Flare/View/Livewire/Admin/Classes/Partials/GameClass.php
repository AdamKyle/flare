<?php

namespace App\Flare\View\Livewire\Admin\Classes\Partials;

use App\Admin\Services\UpdateCharacterStatsService;
use App\Flare\Models\GameClass as GameClassModel;
use Cache;
use Livewire\Component;

class GameClass extends Component
{

    public $gameClass = null;

    protected $rules = [
        'gameClass.name'         => 'required',
        'gameClass.damage_stat'  => 'required',
        'gameClass.to_hit_stat'  => 'required',
        'gameClass.str_mod'      => 'nullable',
        'gameClass.dur_mod'      => 'nullable',
        'gameClass.dex_mod'      => 'nullable',
        'gameClass.chr_mod'      => 'nullable',
        'gameClass.int_mod'      => 'nullable',
        'gameClass.agi_mod'      => 'nullable',
        'gameClass.focus_mod'    => 'nullable',
        'gameClass.accuracy_mod' => 'nullable',
        'gameClass.dodge_mod'    => 'nullable',
        'gameClass.defense_mod'  => 'nullable',
        'gameClass.looting_mod'  => 'nullable',
    ];

    protected $messages = [
        'gameClass.name'        => 'Name is required',
        'gameClass.damage_stat' => 'Damage Stat is required'
    ];

    protected $listeners = ['validateInput'];

    public function mount() {
        if (is_null($this->gameClass)) {
            $this->gameClass = new GameClassModel;
        } else {
            Cache::put('class-' . $this->gameClass->id, $this->gameClass->replicate());
        }
    }

    public function validateInput(string $functionName, int $index) {
        $this->validate();

        $this->gameClass->save();

        if (Cache::has('class-' . $this->gameClass->id)) {
            $oldClass = Cache::pull('class-' . $this->gameClass->id);

            $message = 'Class: ' . $this->gameClass->name . ' Updated. Applying to all characters who are this class.';

            (new UpdateCharacterStatsService())->updateClassStats($oldClass, $this->gameClass->refresh());
        } else {
            $message = 'Class: ' . $this->gameClass->name . ' Created!';
        }

        $this->emitTo('core.form-wizard', 'finish', $index, true, [
            'type'    => 'success',
            'message' => $message,
        ]);
    }

    public function render()
    {
        return view('components.livewire.admin.classes.partials.game-class');
    }
}
