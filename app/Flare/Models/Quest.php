<?php

namespace App\Flare\Models;

use Database\Factories\NpceFactory;
use Database\Factories\NpcFactory;
use Database\Factories\QuestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Flare\Models\Traits\WithSearch;

class Quest extends Model {

    use WithSearch, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'npc_id',
        'item_id',
        'access_to_map_id',
        'gold_dust_cost',
        'shard_cost',
        'gold_cost',
        'reward_item',
        'reward_gold_dust',
        'reward_shards',
        'reward_gold',
        'reward_xp',
        'unlocks_skill',
        'unlocks_skill_type',
        'is_parent',
        'parent_quest_id',
        'secondary_required_item',
        'faction_game_map_id',
        'required_faction_level',
    ];

    protected $casts = [
        'name'               => 'string',
        'item_id'            => 'integer',
        'gold_dust_cost'     => 'integer',
        'shard_cost'         => 'integer',
        'gold_cost'          => 'integer',
        'reward_item'        => 'integer',
        'reward_gold_dust'   => 'integer',
        'reward_shards'      => 'integer',
        'reward_gold'        => 'integer',
        'reward_xp'          => 'integer',
        'unlocks_skill'      => 'boolean',
        'is_parent'          => 'boolean',
        'unlocks_skill_type' => 'integer',
        'parent_quest_id'    => 'integer',
        'faction_game_map_id'     => 'integer',
        'secondary_required_item' => 'integer',
        'required_faction_level'  => 'integer',
    ];

    protected $appends = [
        'required_item_monster',
        'unlocks_skill_name',
        'belongs_to_map_name',
        'secondary_required_quest_item',
    ];

    public function childQuests() {
        return $this->hasMany($this, 'parent_quest_id')
                    ->with(
                'childQuests',
                'rewardItem',
                        'item',
                        'factionMap',
                        'item.dropLocation',
                        'requiredPlane',
                        'npc',
                        'npc.commands'
                    );
    }

    public function parent() {
        return $this->belongsTo($this, 'parent_quest_id');
    }

    public function item() {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function getSecondaryRequiredQuestItemAttribute() {
        return Item::with('dropLocation')->find($this->secondary_required_item);
    }

    public function rewardItem() {
        return $this->belongsTo(Item::class, 'reward_item', 'id');
    }

    public function npc() {
        return $this->belongsTo(Npc::class, 'npc_id', 'id');
    }

    public function requiredPlane() {
        return $this->hasOne(GameMap::class, 'id', 'access_to_map_id');
    }

    public function factionMap() {
        return $this->hasOne(GameMap::class, 'id', 'faction_game_map_id');
    }

    public function getBelongsToMapNameAttribute() {
        if (!is_null($this->npc)) {
            return $this->npc->gameMap->name;
        }

        return null;
    }

    public function getRequiredItemMonsterAttribute() {
        if (!is_null($this->item_id)) {
            return Monster::where('quest_item_id', $this->item_id)->with('gameMap')->first();
        }

        return null;
    }

    public function getUnlocksSkillNameAttribute() {
        if ($this->unlocks_skill) {
            return GameSkill::where('type', $this->unlocks_skill_type)->first()->name;
        }

        return null;
    }

    protected static function newFactory() {
        return QuestFactory::new();
    }
}
