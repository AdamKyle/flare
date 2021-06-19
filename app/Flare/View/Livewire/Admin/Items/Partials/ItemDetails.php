<?php

namespace App\Flare\View\Livewire\Admin\Items\Partials;

use App\Flare\Models\GameSkill;
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
        'quest',
    ];

    public $defaultPositions = [
        'body',
        'leggings',
        'feet',
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

    public $typesThatCanAffectAC = [
        'shield',
        'ring',
        'artifact',
        'body',
        'leggings',
        'feet',
        'hands',
        'sleeves',
        'helmet',
        'gloves',
    ];

    public $typesForBaseHealing = [
        'spell-healing',
        'ring',
        'artifact',
    ];

    public $craftingTypes = [
        'weapon',
        'armour',
        'ring',
        'spell',
        'artifact'
    ];

    public $skills;

    protected $rules = [
        'item.name'                  => 'required',
        'item.type'                  => 'required',
        'item.description'           => 'required',
        'item.can_drop'              => 'nullable',
        'item.craft_only'            => 'nullable',
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
        'item.skill_bonus'           => 'nullable',
        'item.skill_training_bonus'  => 'nullable',
    ];

    protected $messages = [
        'item.name.required'        => 'Item name is required',
        'item.type.required'        => 'Item type is required',
        'item.description.required' => 'Item description is required.',
    ];

    protected $listeners = ['validateInput'];

    public function validateInput(string $functionName, int $index) {
        $itemValidator = resolve(ItemValidator::class);

        $this->validate();

        if ($itemValidator->validate($this, $this->item)) {

            if (is_null($this->item->can_drop)) {
                $this->item->can_drop = true;
            }

            if (is_null($this->item->craft_only)) {
                $this->item->craft_only = false;
            }

            $this->item->save();

            $this->emitTo('core.form-wizard', 'storeModel', $this->item->refresh());
            $this->emitTo('core.form-wizard', $functionName, $index, true);
        }
    }

    public function mount() {

        if (is_null($this->item)) {
            $this->item = new Item;
        }

        $this->skills = GameSkill::all();
    }

    public function render()
    {
        return view('components.livewire.admin.items.partials.item-details');
    }
}
