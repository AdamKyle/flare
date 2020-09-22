<?php

namespace App\Flare\View\Livewire\Admin\Monsters\Partials;

use App\Flare\Models\Item;
use App\Flare\Models\Monster;
use Livewire\Component;

class QuestItem extends Component
{

    public $monster       = null;

    public $questItemList = null;

    protected $rules = [
        'monster.quest_item_id'          => 'nullable',
        'monster.quest_item_drop_chance' => 'nullable',
    ];

    protected $listeners = ['validateInput'];

    public function validateInput(string $functionName, int $index) {

        $this->validate();
        
        if (!is_null($this->monster->quest_item_id) && is_null($this->monster->quest_item_drop_chance)) {
            $this->addError('missing', 'Drop chance is missing for selected quest item.');
        } elseif (is_null($this->monster->quest_item_id) && !is_null($this->monster->quest_item_drop_chance)) {
            $this->addError('missing', 'Item to drop is missing.');
        } elseif (!is_null($this->monster->quest_item_id) && !is_null($this->monster->quest_item_drop_chance)) {
            
            if ($this->monster->quest_item_drop_chance <= 0) {
                $this->addError('missing', 'Drop chance cannot be belo 0.');
            } else {
                $this->monster->save();

                $this->emitTo('create', 'storeModel', $this->monster->refresh()->load('skills', 'questItem'));
                $this->emitTo('create', $functionName, $index, true);
            }
            
        }
    }

    public function mount() {
        if (!is_null($this->monster)) {
            if (is_array($this->monster)) {
                $this->monster = Monster::find($this->monster['id'])->load('questItem');
            }
            
            $this->questItemList = Item::where('type', 'quest')->get();
        }
    }

    public function render()
    {
        return view('components.livewire.admin.monsters.partials.quest-item');
    }
}
