<?php

namespace App\Flare\View\Livewire\Admin\Npcs\Partials;

use App\Flare\Cache\CoordinatesCache;
use App\Flare\Models\GameMap;
use App\Flare\Models\Npc;
use App\Flare\Values\NpcTypes;
use Livewire\Component;

class Details extends Component {

    protected $rules = [
        'npc.name'                     => 'required',
        'npc.real_name'                => 'required',
        'npc.type'                     => 'required',
        'npc.text_command_to_message'  => 'required',
        'npc.game_map_id'              => 'required',
        'npc.x_position'               => 'required',
        'npc.y_position'               => 'required',
        'npc.moves_around_map'         => 'nullable',
        'npc.must_be_at_same_location' => 'nullable',
    ];

    protected $listeners = ['validateInput'];

    protected $messages = [
        'npc.name'                     => 'Name is required.',
        'npc.type'                     => 'Type must be selected.',
        'npc.text_command_to_message'  => 'You must enter a command to message the npc.',
        'npc.game_map_id'              => 'You must select a game map.',
        'npc.x_position'               => 'You must assign a x position.',
        'npc.y_position'               => 'You must assign a y position.',
    ];

    public $npc;

    public $types       = [];

    public $gameMaps    = [];

    public $coordinates = [];

    public function validateInput(string $functionName, int $index) {
        $this->npc->real_name = $this->npc->name;
        $this->npc->name      = str_replace(' ', '', $this->npc->name);

        $this->npc->text_command_to_message = '/m ' . $this->npc->name . ':';

        $this->validate();

        if (is_null($this->npc->moves_around_map)) {
            $this->npc->moves_around_map = false;
        }

        if (is_null($this->npc->must_be_at_same_location)) {
            $this->npc->must_be_at_same_location = false;
        }

        $this->npc->save();

        $this->emitTo('core.form-wizard', 'storeModel', $this->npc);
        $this->emitTo('core.form-wizard', $functionName, $index, true);
    }

    public function render() {
        return view('components.livewire.admin.npcs.partials.details');
    }

    public function mount(CoordinatesCache $coordinatesCache) {
        $this->types       = NpcTypes::getNamedValues();
        $this->gameMaps    = GameMap::all()->pluck('name', 'id');
        $this->coordinates = $coordinatesCache->getFromCache();

        if (is_null($this->npc)) {
            $this->npc = new Npc;
        }
    }
}
