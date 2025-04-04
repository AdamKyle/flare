<?php

namespace App\Flare\Models;

use App\Flare\Values\FeatureTypes;
use App\Game\Events\Values\EventType;
use Database\Factories\QuestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'npc_id',
        'item_id',
        'raid_id',
        'required_quest_id',
        'parent_chain_quest_id',
        'required_quest_chain',
        'reincarnated_times',
        'access_to_map_id',
        'gold_dust_cost',
        'shard_cost',
        'gold_cost',
        'copper_coin_cost',
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
        'before_completion_description',
        'after_completion_description',
        'unlocks_feature',
        'unlocks_passive_id',
        'only_for_event',
        'assisting_npc_id',
        'required_fame_level',
    ];

    protected $casts = [
        'name' => 'string',
        'item_id' => 'integer',
        'raid_id' => 'integer',
        'required_quest_id' => 'integer',
        'parent_chain_quest_id' => 'integer',
        'required_quest_chain' => 'array',
        'reincarnated_times' => 'integer',
        'gold_dust_cost' => 'integer',
        'shard_cost' => 'integer',
        'gold_cost' => 'integer',
        'copper_coin_cost' => 'integer',
        'reward_item' => 'integer',
        'reward_gold_dust' => 'integer',
        'reward_shards' => 'integer',
        'reward_gold' => 'integer',
        'reward_xp' => 'integer',
        'unlocks_skill' => 'boolean',
        'is_parent' => 'boolean',
        'unlocks_skill_type' => 'integer',
        'parent_quest_id' => 'integer',
        'faction_game_map_id' => 'integer',
        'secondary_required_item' => 'integer',
        'required_faction_level' => 'integer',
        'unlocks_feature' => 'integer',
        'unlocks_passive_id' => 'integer',
        'only_for_event' => 'integer',
        'required_fame_level' => 'integer',
    ];

    protected $appends = [
        'belongs_to_map_name',
        'required_quest_chain_details',
        'unlocks_passive_name',
    ];

    public function eventType(): ?EventType
    {
        if (! is_null($this->only_for_event)) {
            return new EventType($this->only_for_event);
        }

        return null;
    }

    public static function getAllQuestsInOrder()
    {
        $quests = self::where('is_parent', true)
            ->with('childQuests')
            ->get();

        $result = collect();

        foreach ($quests as $quest) {
            $result->push($quest);
            $result = $result->merge(self::flattenChildQuests($quest));
        }

        $allRequiredIds = $result->pluck('required_quest_chain')
            ->filter()
            ->flatten()
            ->unique();

        $questNameMap = self::whereIn('id', $allRequiredIds)->pluck('name', 'id')->toArray();

        return $result->map(function ($quest) use ($questNameMap) {
            $quest->required_quest_chain_names = collect($quest->required_quest_chain ?? [])
                ->map(fn($id) => $questNameMap[$id] ?? null)
                ->filter()
                ->values()
                ->all();

            return $quest;
        })->values();
    }

    private static function flattenChildQuests(Quest $quest)
    {
        $flattened = collect();

        foreach ($quest->childQuests as $child) {
            $flattened->push($child);
            $flattened = $flattened->merge(self::flattenChildQuests($child));
        }

        return $flattened;
    }

    public function childQuests()
    {
        return $this->hasMany($this, 'parent_quest_id')
            ->with(
                'childQuests'
            );
    }

    public function loadRelations()
    {
        return $this->load(
            'rewardItem',
            'item',
            'requiredQuest',
            'factionMap',
            'item.dropLocation',
            'secondaryItem',
            'secondaryItem.dropLocation',
            'requiredPlane',
            'factionLoyaltyNpc',
            'factionLoyaltyNpc.gameMap',
            'npc',
            'npc.gameMap',
            'raid',
        );
    }

    public function parent()
    {
        return $this->belongsTo($this, 'parent_quest_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function requiredQuest()
    {
        return $this->belongsTo($this, 'required_quest_id')->with('raid');
    }

    public function secondaryItem()
    {
        return $this->belongsTo(Item::class, 'secondary_required_item', 'id');
    }

    public function passive()
    {
        return $this->belongsTo(PassiveSkill::class, 'unlocks_passive_id', 'id');
    }

    public function rewardItem()
    {
        return $this->belongsTo(Item::class, 'reward_item', 'id');
    }

    public function npc()
    {
        return $this->belongsTo(Npc::class, 'npc_id', 'id');
    }

    public function factionLoyaltyNpc()
    {
        return $this->belongsTo(Npc::class, 'assisting_npc_id', 'id');
    }

    public function requiredPlane()
    {
        return $this->hasOne(GameMap::class, 'id', 'access_to_map_id');
    }

    public function factionMap()
    {
        return $this->hasOne(GameMap::class, 'id', 'faction_game_map_id');
    }

    public function raid()
    {
        return $this->hasOne(Raid::class, 'id', 'raid_id');
    }

    public function unlocksFeature(): ?FeatureTypes
    {
        if (! is_null($this->unlocks_feature)) {
            return new FeatureTypes($this->unlocks_feature);
        }

        return null;
    }

    public function getBelongsToMapNameAttribute()
    {
        if (! is_null($this->npc)) {
            return $this->npc->gameMap->name;
        }

        return null;
    }

    public function getUnlocksPassiveNameAttribute() {
        if (!is_null($this->unlocks_passive_id)) {
            return $this->passive->name;
        }
    }

    public function getRequiredItemMonsterAttribute()
    {
        if (! is_null($this->item_id)) {
            return Monster::where('quest_item_id', $this->item_id)->with('gameMap')->first();
        }

        return null;
    }

    public function getUnlocksSkillNameAttribute()
    {
        if ($this->unlocks_skill) {
            return GameSkill::where('type', $this->unlocks_skill_type)->first()->name;
        }

        return null;
    }

    public function getRequiredQuestChainDetailsAttribute() {

        if (is_null($this->required_quest_chain)) {
            return null;
        }

        $firstQuest = self::find($this->required_quest_chain[0]);

        return [
            'quest_ids' => $this->required_quest_chain,
            'quest_names' => self::whereIn('id', $this->required_quest_chain)->pluck('name')->toArray(),
            'map_name' => $firstQuest->npc->gameMap->name,
            'starts_with_npc' => $firstQuest->npc->real_name,
        ];
    }

    protected static function newFactory()
    {
        return QuestFactory::new();
    }
}
