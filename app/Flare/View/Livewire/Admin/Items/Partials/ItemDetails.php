<?php

namespace App\Flare\View\Livewire\Admin\Items\Partials;

use App\Flare\Models\Item;
use App\Flare\View\Livewire\Admin\Items\Validators\ItemValidator;
use Livewire\Component;
use Livewire\Request;

class ItemDetails extends Component
{
    public $item;

    public $types = [
        'weapon',
        'body',
        'shield',
        'leggings',
        'feet',
        'sleeves',
        'helmet',
        'gloves',
        'ring',
        'spell-healing',
        'spell-damage',
        'artifact',
    ];

    public $defaultPositions = [
        'body',
        'legs',
        'feet',
        'hands',
        'sleeves',
        'helmet',
        'gloves',
    ];

    public $itemsWithOutDefaultPosition = [
        'weapon',
        'shield',
        'ring',
        'artifact',
        'spell-damage',
        'spell-healing',
    ];

    public $craftingTypes = [
        'weapon',
        'armour',
        'ring',
        'spell',
        'artifact'
    ];

    protected $listeners = ['validateInput'];

    protected $rules = [
        'item.name'                  => 'required',
        'item.type'                  => 'required',
        'item.description'           => 'required',
        'item.default_position'      => 'nullable',
        'item.base_damage'           => 'nullable',
        'item.base_ac'               => 'nullable',
        'item.base_healing'          => 'nullable',
        'item.can_craft'             => 'nullable',
        'item.crafting_type'         => 'nullable',
        'item.cost'                  => 'nullable',
        'item.skill_level_required'  => 'nullable',
        'item.skill_level_trivial'   => 'nullable',
        'item.skill_name'            => 'nullable',
        'item.skill_training_bonus'  => 'nullable',  
    ];

    protected $messages = [
        'item.name.required'        => 'Item name is required',
        'item.type.required'        => 'Item type is required',
        'item.description.required' => 'Item description is required.',
    ];
    

    public function validateInput(string $functionName, int $index) {
        $itemValidator = resolve(ItemValidator::class);
        
        $this->validate();

        if ($itemValidator->validate($this, $this->item)) {

            $this->item->save();

            $this->emitTo('manage', 'storeModel', $this->item->refresh());
            $this->emitTo('manage', $functionName, $index, true);
        }
    }

    public function mount() {

        if (is_null($this->item)) {
            $this->item = new Item;
        }
    }

    public function render()
    {
        return view('components.livewire.admin.items.partials.item-details');
    }
}
