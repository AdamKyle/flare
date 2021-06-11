<?php

namespace App\Flare\View\Livewire\Admin\Npcs\Partials;

use Livewire\Component;

class Details extends Component {

    protected $rules = [
        'npc.name'                     => 'required',
        'npc.type'                     => 'required',
        'npc.text_command_to_message'  => 'required',
        'npc.game_map_id'              => 'required',
        'npc.moves_around_map'         => 'nullable',
        'npc.must_be_at_same_location' => 'nullable',
    ];

    protected $listeners = ['validateInput'];

    protected $messages = [
        'npc.name'                     => 'Name is required.',
        'npc.type'                     => 'Type must be selected.',
        'npc.text_command_to_message'  => 'You must enter a command to message the npc.',
        'npc.game_map_id'              => 'You must select a game map.',
    ];

    public function validateInput(string $functionName, int $index) {
        $this->validate();

        $this->npc->save();

        $this->emitTo('core.form-wizard', 'storeModel', $this->monster);
        $this->emitTo('core.form-wizard', $functionName, $index, true);
    }

    public function render()
    {
        return view('components.livewire.admin.npcs.partials.details');
    }
}
