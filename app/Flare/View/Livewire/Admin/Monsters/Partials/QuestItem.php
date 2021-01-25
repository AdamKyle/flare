<?php

namespace App\Flare\View\Livewire\Admin\Monsters\Partials;

use App\Flare\Models\Item;
use App\Flare\Models\Monster;
use Livewire\Component;

class QuestItem extends Component
{

    public $monster       = null;

    public $questItemList = null;

    public $editing       = false;

    protected $rules = [
        'monster.quest_item_id'          => 'nullable',
        'monster.quest_item_drop_chance' => 'nullable',
    ];

    protected $listeners = ['validateInput', 'update'];

    public function validateInput(string $functionName, int $index) {

        $this->validate();
        
        if (!is_null($this->monster->quest_item_id) && is_null($this->monster->quest_item_drop_chance)) {
            $this->addError('missing', 'Drop chance is missing for selected quest item.');
        } elseif (is_null($this->monster->quest_item_id) && !is_null($this->monster->quest_item_drop_chance)) {
            $this->addError('missing', 'Item to drop is missing.');
        } elseif (!is_null($this->monster->quest_item_id) && !is_null($this->monster->quest_item_drop_chance)) {
            
            if ($this->monster->quest_item_drop_chance <= 0) {
                $this->addError('missing', 'Drop chance cannot be below or equal to 0.');
            } else {
                $this->monster->save();

                $message = 'Created monster: ' . $this->monster->refresh()->name;

                if ($this->editing) {
                    $message = 'Updated monster: ' . $this->monster->refresh()->name;
                }

                $this->emitTo('core.form-wizard', $functionName, $index, true, [
                    'type'    => 'success',
                    'message' => $message,
                ]);
            }   
        } else {
            $this->monster->save();

            $message = 'Created monster: ' . $this->monster->refresh()->name;

            $this->emitTo('core.form-wizard', $functionName, $index, true, [
                'type'    => 'success',
                'message' => $message,
            ]);
        }
    }

    public function update($id) {
        $this->monster = Monster::find($id)->load('questItem');
    }

    public function mount() {
        $this->questItemList = Item::where('type', 'quest')->get();
    }

    public function render()
    {
        return view('components.livewire.admin.monsters.partials.quest-item');
    }
}
