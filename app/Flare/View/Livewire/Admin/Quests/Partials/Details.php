<?php

namespace App\Flare\View\Livewire\Admin\Quests\Partials;

use App\Flare\Models\Item;
use App\Flare\Models\Npc;
use App\Flare\Models\Quest;
use App\Flare\Values\NpcTypes;
use App\Game\Skills\Values\SkillTypeValue;
use Livewire\Component;

class Details extends Component
{
    protected $rules = [
        'quest.name'               => 'required',
        'quest.npc_id'             => 'required',
        'quest.item_id'            => 'nullable',
        'quest.gold_dust_cost'     => 'nullable',
        'quest.shard_cost'         => 'nullable',
        'quest.gold_cost'          => 'nullable',
        'quest.reward_item'        => 'nullable',
        'quest.reward_gold_dust'   => 'nullable',
        'quest.reward_shards'      => 'nullable',
        'quest.reward_gold'        => 'nullable',
        'quest.reward_xp'          => 'nullable',
        'quest.unlocks_skill'      => 'nullable',
        'quest.unlocks_skill_type' => 'nullable',
    ];

    protected $listeners = ['validateInput'];

    protected $messages = [
        'quest.name'   => 'Quest needs a name.',
        'quest.npc_id' => 'NPC is required.',
    ];

    public $quest      = [];
    public $items      = [];
    public $npcs       = [];
    public $skillTypes = [];

    public function validateInput(string $functionName, int $index) {

        if (is_null($this->quest->unlocks_skill)) {
            $this->quest->unlocks_skill      = false;
            $this->quest->unlocks_skill_type = null;
        }

        $this->validate();

        $this->quest->save();

        $this->emitTo('core.form-wizard', 'storeModel', $this->quest);
        $this->emitTo('core.form-wizard', $functionName, $index, true);
    }

    public function mount() {
        if (is_null($this->quest)) {
            $this->quest = new Quest;
        }

        $this->items      = Item::where('type', 'quest')->pluck('name', 'id');
        $this->npcs       = Npc::where('type', NpcTypes::QUEST_GIVER)->pluck('real_name', 'id');
        $this->skillTypes = SkillTypeValue::$namedValues;
    }

    public function render() {
        return view('components.livewire.admin.quests.partials.details');
    }
}
