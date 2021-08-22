<?php

namespace App\Flare\View\Livewire\Admin\Items\Partials;

use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\View\Livewire\Admin\Items\Validators\ItemValidator;
use App\Game\Skills\Values\SkillTypeValue;
use Livewire\Component;
use Livewire\Request;

class ItemDetails extends Component
{
    public $item;

    public $showUsabillityError = false;

    public $affectsSkillError   = false;

    public $skillTypes = [];

    public $types = [
        'weapon',
        'bow',
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
        'alchemy',
    ];

    public $defaultPositions = [
        'bow',
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
        'artifact',
        'alchemy',
    ];

    public $skills;

    public $lastsFor = 0;

    protected $rules = [
        'item.name'                             => 'required',
        'item.type'                             => 'required',
        'item.description'                      => 'required',
        'item.can_drop'                         => 'nullable',
        'item.craft_only'                       => 'nullable',
        'item.default_position'                 => 'nullable',
        'item.base_damage'                      => 'nullable',
        'item.base_ac'                          => 'nullable',
        'item.base_healing'                     => 'nullable',
        'item.can_craft'                        => 'nullable',
        'item.crafting_type'                    => 'nullable',
        'item.cost'                             => 'nullable',
        'item.gold_dust_cost'                   => 'nullable',
        'item.shards_cost'                      => 'nullable',
        'item.skill_level_required'             => 'nullable',
        'item.skill_level_trivial'              => 'nullable',
        'item.skill_name'                       => 'nullable',
        'item.skill_bonus'                      => 'nullable',
        'item.base_damage_mod_bonus'            => 'nullable',
        'item.base_healing_mod_bonus'           => 'nullable',
        'item.base_ac_mod_bonus'                => 'nullable',
        'item.fight_time_out_mod_bonus'         => 'nullable',
        'item.move_time_out_mod_bonus'          => 'nullable',
        'item.skill_training_bonus'             => 'nullable',
        'item.market_sellable'                  => 'nullable',
        'item.usable'                           => 'nullable',
        'item.damages_kingdoms'                 => 'nullable',
        'item.kingdom_damage'                   => 'nullable',
        'item.lasts_for'                        => 'nullable',
        'item.stat_increase'                    => 'nullable',
        'item.increase_stat_by'                 => 'nullable',
        'item.affects_skill_type'               => 'nullable',
        'item.increase_skill_bonus_by'          => 'nullable',
        'item.increase_skill_training_bonus_by' => 'nullable',
        'item.can_resurrect'                    => 'nullable',
        'item.resurrection_chance'              => 'nullable',
        'item.spell_evasion'                    => 'nullable',
        'item.artifact_annulment'               => 'nullable',
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
                $this->item->can_drop = false;
            }

            if (is_null($this->item->craft_only)) {
                $this->item->craft_only = false;
            }

            if (is_null($this->item->market_sellable)) {
                $this->item->market_sellable = false;
            }

            if (is_null($this->item->can_resurrect)) {
                $this->item->can_resurrect = false;
            }

            if (is_null($this->item->usable)) {
                $this->item->usable             = false;
                $this->item->lasts_for          = null;
                $this->item->damages_kingdoms   = null;
                $this->item->stat_increase      = null;
                $this->item->affects_skill_type = null;
                $this->item->gold_dust_cost     = 0;
                $this->item->shards_cost        = 0;
            }

            if (is_null($this->item->damages_kingdoms)) {
                $this->item->damages_kingdoms = false;
                $this->item->kingdom_damage   = null;
            } else if ($this->item->damages_kingdoms) {
                // A item that damages kingdoms cannot affect skills or stats.
                $this->item->lasts_for          = null;
                $this->item->stat_increase      = null;
                $this->item->affects_skill_type = null;
            }

            if (empty($this->item->stat_increase)) {
                $this->item->stat_increase    = false;
                $this->item->increase_stat_by = null;
            }

            if (is_null($this->item->affects_skill_type)) {
                $this->item->affects_skill_type               = null;
                $this->item->increase_skill_bonus_by          = null;
                $this->item->increase_skill_training_bonus_by = null;
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

        $this->skills      = GameSkill::all();
        $this->skillTypes = SkillTypeValue::$namedValues;
    }

    public function render()
    {
        return view('components.livewire.admin.items.partials.item-details');
    }
}
