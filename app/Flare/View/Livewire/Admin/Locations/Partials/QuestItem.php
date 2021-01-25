<?php

namespace App\Flare\View\Livewire\Admin\Locations\Partials;

use App\Flare\Models\Item;
use App\Flare\Models\Location;
use Livewire\Component;

class QuestItem extends Component
{

    public $location;

    public $items;

    public $editing = false;

    protected $rules = [
        'location.quest_reward_item_id' => 'nullable'
    ];

    protected $listeners = ['validateInput', 'update'];

    public function update($id) {
        $this->location = Location::find($id);
    }

    public function validateInput(string $functionName, int $index) {
        $this->validate();

        $this->location->save();

        $message = 'Created location: ' . $this->location->refresh()->name;

        if ($this->editing) {
            $message = 'Updated location: ' . $this->location->refresh()->name;
        }

        $this->emitTo('core.form-wizard', $functionName, $index, true, [
            'type'    => 'success',
            'message' => $message,
        ]);
    }

    public function mount() {
        $this->items = Item::where('type', 'quest')->pluck('name', 'id')->toArray();
    }

    public function render()
    {
        return view('components.livewire.admin.locations.partials.quest-item');
    }
}
