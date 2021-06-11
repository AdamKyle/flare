<?php

namespace App\Flare\View\Livewire\Admin\Npcs\Partials;

use App\Flare\Models\Npc;
use App\Flare\Models\NpcCommand;
use App\Flare\Values\NpcCommandTypes;
use Livewire\Component;

class Commands extends Component {
    protected $rules = [
        'npcCommand.npc_id'       => 'required',
        'npcCommand.command'      => 'required',
        'npcCommand.command_type' => 'required',
    ];

    protected $messages = [
        'npcCommand.command'      => 'Command is required.',
        'npcCommand.command_type' => 'Command type is required.',
        'npcCommand.npc_id'       => 'Npc Id is required.',
    ];

    protected $listeners = ['validateInput', 'update'];

    public $npcCommand;

    public $npc;

    public $editing = false;

    public $commandTypes = [];

    public function mount() {
        $this->commandTypes = NpcCommandTypes::getNamedValues();
    }

    public function update($id) {
        $this->npc = Npc::find($id);

        if ($this->npc->commands->isEmpty()) {
            $this->npcCommand = new NpcCommand;
            $this->npcCommand->npc_id = $id;
        } else {
            $this->npcCommand = $this->npc->commands->first();
        }
    }

    public function validateInput(string $functionName, int $index) {
        $this->validate();

        $this->npcCommand->save();

        $message = 'Created NPC: ' . $this->npc->name;

        if ($this->editing) {
            $message = 'Updated NPC: ' . $this->npc->name;
        }

        $this->emitTo('core.form-wizard', $functionName, $index, true, [
            'type'    => 'success',
            'message' => $message,
        ]);
    }

    public function render() {
        return view('components.livewire.admin.npcs.partials.commands');
    }
}
