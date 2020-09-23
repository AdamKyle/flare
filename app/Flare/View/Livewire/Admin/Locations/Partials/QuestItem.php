<?php

namespace App\Flare\View\Livewire\Admin\Locations\Partials;

use App\Flare\Models\Item;
use App\Flare\Models\Location;
use Livewire\Component;

class QuestItem extends Component
{

    public $location;

    public $items;

    protected $rules = [
        'location.quest_reward_item_id' => 'nullable'
    ];

    protected $listeners = ['validateInput'];

    public function validateInput(string $functionName, int $index) {
        $this->validate();

        if (!is_null($this->location->quest_reward_item_id)) {
            $this->location->save();

            $this->emitTo('manage', 'storeModel', $this->location->refresh());
            $this->emitTo('manage', $functionName, $index, true);
        }
    }

    public function mount() {
        if (is_array($this->location)) {
            $this->location = Location::find($this->location['id']);
        }

        $this->items = Item::where('type', 'quest')->pluck('name', 'id')->toArray();
    }

    public function render()
    {
        return view('components.livewire.admin.locations.partials.quest-item');
    }
}
