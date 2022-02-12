<?php

namespace App\Flare\View\Livewire\Admin\Quests\Partials;

use App\Admin\Jobs\ResetCharacterQuestStorage;
use Cache;
use App\Flare\Models\GameMap;
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
        'quest.copper_coin_cost'   => 'nullable',
        'quest.reward_item'        => 'nullable',
        'quest.reward_gold_dust'   => 'nullable',
        'quest.reward_shards'      => 'nullable',
        'quest.reward_gold'        => 'nullable',
        'quest.reward_xp'          => 'nullable',
        'quest.unlocks_skill'      => 'nullable',
        'quest.unlocks_skill_type' => 'nullable',
        'quest.is_parent'          => 'nullable',
        'quest.parent_quest_id'    => 'nullable',
        'quest.faction_game_map_id'     => 'nullable',
        'quest.secondary_required_item' => 'nullable',
        'quest.required_faction_level'  => 'nullable',
        'quest.access_to_map_id'        => 'nullable',
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
    public $quests     = [];
    public $gameMaps   = [];

    public function validateInput(string $functionName, int $index) {

        if (is_null($this->quest->unlocks_skill)) {
            $this->quest->unlocks_skill      = false;
            $this->quest->unlocks_skill_type = null;
        }

        if (is_null($this->quest->is_parent)) {
            $this->quest->is_parent = false;
        }

        $this->validate();

        $this->quest->save();

        Cache::delete('npc-quests');
        Cache::delete('all-quests');

        ResetCharacterQuestStorage::dispatch();

        $this->emitTo('core.form-wizard', 'storeModel', $this->quest);
        $this->emitTo('core.form-wizard', $functionName, $index, true);
    }

    public function mount() {
        if (is_null($this->quest)) {
            $this->quest = new Quest;
        }

        $this->items      = Item::where('type', 'quest')->pluck('name', 'id');
        $this->npcs       = Npc::where('type', NpcTypes::QUEST_GIVER)->pluck('real_name', 'id');
        $this->quests     = Quest::pluck('name', 'id');
        $this->skillTypes = SkillTypeValue::$namedValues;
        $this->gameMaps   = GameMap::pluck('name', 'id');
    }

    public function render() {
        return view('components.livewire.admin.quests.partials.details');
    }
}
