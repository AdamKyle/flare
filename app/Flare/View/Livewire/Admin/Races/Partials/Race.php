<?php

namespace App\Flare\View\Livewire\Admin\Races\Partials;

use Cache;
use Livewire\Component;
use App\Admin\Services\UpdateCharacterStatsService;
use App\Flare\Models\GameRace;

class Race extends Component
{

    public $race = null;

    protected $rules = [
        'race.name'         => 'required',
        'race.str_mod'      => 'nullable',
        'race.dur_mod'      => 'nullable',
        'race.dex_mod'      => 'nullable',
        'race.chr_mod'      => 'nullable',
        'race.int_mod'      => 'nullable',
        'race.agi_mod'      => 'nullable',
        'race.focus_mod'    => 'nullable',
        'race.accuracy_mod' => 'nullable',
        'race.dodge_mod'    => 'nullable',
        'race.defense_mod'  => 'nullable',
        'race.looting_mod'  => 'nullable',
    ];

    protected $messages = [
        'race.name' => 'Name is required',
    ];

    protected $listeners = ['validateInput'];

    public function mount() {
        if (is_null($this->race)) {
            $this->race = new GameRace;
        } else {
            Cache::put('race-' . $this->race->id, $this->race->replicate());
        }
    }

    public function validateInput(string $functionName, int $index) {
        $this->validate();

        $this->race->save();

        if (Cache::has('race-' . $this->race->id)) {
            $oldRace = Cache::pull('race-' . $this->race->id);

            $message = 'Race: ' . $this->race->name . ' Updated. Applying to all characters who are this race.';

            Cache::put('updating-characters', true);

            (new UpdateCharacterStatsService())->updateRacialStats($oldRace, $this->race->refresh());
        } else {
            $message = 'Race: ' . $this->race->name . ' Created!';
        }

        $this->emitTo('core.form-wizard', 'finish', $index, true, [
            'type'    => 'success',
            'message' => $message,
        ]);
    }

    public function render()
    {
        return view('components.livewire.admin.races.partials.race');
    }
}
