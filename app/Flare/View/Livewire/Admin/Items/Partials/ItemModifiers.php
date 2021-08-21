<?php

namespace App\Flare\View\Livewire\Admin\Items\Partials;

use App\Flare\Values\ItemEffectsValue;
use Livewire\Component;
use App\Flare\Models\Item;

class ItemModifiers extends Component
{

    public $item;

    public $effects = [
        ItemEffectsValue::WALK_ON_WATER,
        ItemEffectsValue::WALK_ON_DEATH_WATER,
        ItemEffectsValue::LABYRINTH,
        ItemEffectsValue::DUNGEON,
    ];

    public $editing = false;

    protected $rules = [
        'item.base_damage_mod'          => 'nullable',
        'item.base_healing_mod'         => 'nullable',
        'item.base_ac_mod'              => 'nullable',
        'item.str_mod'                  => 'nullable',
        'item.dur_mod'                  => 'nullable',
        'item.dex_mod'                  => 'nullable',
        'item.chr_mod'                  => 'nullable',
        'item.int_mod'                  => 'nullable',
        'item.agi_mod'                  => 'nullable',
        'item.focus_mod'                => 'nullable',
        'item.effect'                   => 'nullable',
        'item.fight_time_out_mod_bonus' => 'nullable',
        'item.base_damage_mod_bonus'    => 'nullable',
        'item.base_healing_mod_bonus'   => 'nullable',
        'item.base_ac_mod_bonus'        => 'nullable',
        'item.move_time_out_mod_bonus'  => 'nullable',
    ];

    protected $listeners = ['validateInput', 'update'];

    public function mount() {
        if (is_array($this->item)) {
            $this->item = Item::find($this->item['id']);
        }
    }

    public function update($id) {
        $this->item = Item::find($id);
    }

    public function validateInput(string $functionName, int $index) {
        $this->validate();

        $this->item->save();

        $message = 'Created Item: ' . $this->item->refresh()->name;

        if ($this->editing) {
            $message = 'Updated Item: ' . $this->item->refresh()->name;
        }

        $this->emitTo('core.form-wizard', $functionName, $index, true, [
            'type'    => 'success',
            'message' => $message,
        ]);
    }

    public function render()
    {
        return view('components.livewire.admin.items.partials.item-modifiers');
    }
}
