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
        'unlocks_skill_type' => 'integer',
    ];


    public function item() {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function rewardItem() {
        return $this->belongsTo(Item::class, 'reward_item', 'id');
    }

    public function npc() {
        return $this->belongsTo(Npc::class, 'npc_id', 'id');
    }

    protected static function newFactory() {
        return QuestFactory::new();
    }
}
