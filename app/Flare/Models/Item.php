<?php

namespace App\Flare\Models;

use App\Flare\Models\Traits\CalculateSkillBonus;
use Bkwld\Cloner\Cloneable;
use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use CalculateSkillBonus, Cloneable, HasFactory;

    protected $fillable = [
        'name',
        'item_suffix_id',
        'item_prefix_id',
        'drop_location_id',
        'type',
        'alchemy_type',
        'default_position',
        'base_damage',
        'base_ac',
        'base_healing',
        'cost',
        'gold_dust_cost',
        'shards_cost',
        'copper_coin_cost',
        'base_damage_mod',
        'description',
        'base_healing_mod',
        'base_ac_mod',
        'str_mod',
        'dur_mod',
        'dex_mod',
        'chr_mod',
        'int_mod',
        'agi_mod',
        'focus_mod',
        'effect',
        'can_craft',
        'skill_name',
        'skill_training_bonus',
        'skill_bonus',
        'fight_time_out_mod_bonus',
        'move_time_out_mod_bonus',
        'skill_level_required',
        'skill_level_trivial',
        'crafting_type',
        'market_sellable',
        'can_drop',
        'craft_only',
        'usable',
        'damages_kingdoms',
        'kingdom_damage',
        'lasts_for',
        'stat_increase',
        'increase_stat_by',
        'affects_skill_type',
        'can_resurrect',
        'resurrection_chance',
        'spell_evasion',
        'artifact_annulment',
        'increase_skill_bonus_by',
        'increase_skill_training_bonus_by',
        'healing_reduction',
        'affix_damage_reduction',
        'devouring_light',
        'devouring_darkness',
        'parent_id',
        'xp_bonus',
        'ignores_caps',
        'can_use_on_other_items',
        'holy_level',
        'holy_stacks',
        'ambush_chance',
        'ambush_resistance',
        'counter_chance',
        'counter_resistance',
        'is_mythic',
        'is_cosmic',
        'specialty_type',
        'gold_bars_cost',
        'can_stack',
        'gains_additional_level',
        'unlocks_class_id',
        'socket_count',
        'has_gems_socketed',
        'item_skill_id',
    ];

    protected $casts = [
        'base_damage' => 'integer',
        'base_healing' => 'integer',
        'base_ac' => 'integer',
        'cost' => 'integer',
        'gold_dust_cost' => 'integer',
        'shards_cost' => 'integer',
        'copper_coin_cost' => 'integer',
        'parent_id' => 'integer',
        'holy_level' => 'integer',
        'holy_stacks' => 'integer',
        'gold_bars_cost' => 'integer',
        'unlocks_class_id' => 'integer',
        'socket_count' => 'integer',
        'str_mod' => 'float',
        'dur_mod' => 'float',
        'dex_mod' => 'float',
        'chr_mod' => 'float',
        'int_mod' => 'float',
        'agi_mod' => 'float',
        'focus_mod' => 'float',
        'skill_training_bonus' => 'float',
        'skill_bonus' => 'float',
        'base_damage_mod_bonus' => 'float',
        'base_healing_mod_bonus' => 'float',
        'base_ac_mod_bonus' => 'float',
        'fight_time_out_mod_bonus' => 'float',
        'move_time_out_mod_bonus' => 'float',
        'can_craft' => 'boolean',
        'can_resurrect' => 'boolean',
        'randomly_generated' => 'boolean',
        'has_gems_socketed' => 'boolean',
        'skill_level_required' => 'integer',
        'skill_level_trivial' => 'integer',
        'craft_only' => 'boolean',
        'can_drop' => 'boolean',
        'market_sellable' => 'boolean',
        'usable' => 'boolean',
        'damages_kingdoms' => 'boolean',
        'ignores_caps' => 'boolean',
        'stat_increase' => 'boolean',
        'can_use_on_other_items' => 'boolean',
        'is_mythic' => 'boolean',
        'can_stack' => 'boolean',
        'gains_additional_level' => 'boolean',
        'kingdom_damage' => 'float',
        'lasts_for' => 'integer',
        'increase_stat_by' => 'float',
        'affects_skill_type' => 'integer',
        'increase_skill_bonus_by' => 'float',
        'increase_skill_training_bonus_by' => 'float',
        'resurrection_chance' => 'float',
        'spell_evasion' => 'float',
        'artifact_annulment' => 'float',
        'healing_reduction' => 'float',
        'affix_damage_reduction' => 'float',
        'devouring_light' => 'float',
        'devouring_darkness' => 'float',
        'xp_bonus' => 'float',
        'ambush_chance' => 'float',
        'ambush_resistance' => 'float',
        'counter_chance' => 'float',
        'counter_resistance' => 'float',
        'is_cosmic' => 'boolean',
    ];

    protected $appends = [
        'affix_name',
        'affix_count',
        'required_monster',
        'required_quest',
        'locations',
        'holy_stack_devouring_darkness',
        'holy_stack_stat_bonus',
        'holy_stacks_applied',
        'is_unique',
    ];

    public function itemSkill()
    {
        return $this->hasOne(ItemSkill::class, 'id', 'item_skill_id')->with('children');
    }

    public function itemSkillProgressions()
    {
        return $this->hasMany(ItemSkillProgression::class, 'item_id', 'id');
    }

    public function inventorySlots()
    {
        return $this->hasMany(InventorySlot::class, 'item_id', 'id');
    }

    public function inventorySetSlots()
    {
        return $this->hasMany(SetSlot::class, 'item_id', 'id');
    }

    public function marketListings()
    {
        return $this->hasMany(MarketBoard::class, 'item_id', 'id');
    }

    public function marketHistory()
    {
        return $this->hasMany(MarketHistory::class, 'item_id', 'id');
    }

    public function itemSuffix()
    {
        return $this->hasOne(ItemAffix::class, 'id', 'item_suffix_id');
    }

    public function itemPrefix()
    {
        return $this->hasOne(ItemAffix::class, 'id', 'item_prefix_id');
    }

    public function appliedHolyStacks()
    {
        return $this->hasMany(HolyStack::class, 'item_id', 'id');
    }

    public function sockets()
    {
        return $this->hasMany(ItemSocket::class, 'item_id', 'id');
    }

    public function dropLocation()
    {
        return $this->hasOne(Location::class, 'id', 'drop_location_id')->with('map');
    }

    public function unlocksClass()
    {
        return $this->hasOne(GameClass::class, 'id', 'unlocks_class_id');
    }

    public function children()
    {
        return $this->hasMany($this, 'parent_id')->with('children');
    }

    public function parent()
    {
        return $this->belongsTo($this, 'parent_id');
    }

    public function getAffixNameAttribute()
    {
        $itemPrefix = ItemAffix::find($this->item_prefix_id);
        $itemSuffix = ItemAffix::find($this->item_suffix_id);
        $itemName = '';

        if (! is_null($itemPrefix)) {
            $itemName = '*'.$itemPrefix->name.'* '.$this->name;
        }

        if (! is_null($itemSuffix)) {
            $itemName .= $itemName !== '' ? ' *'.$itemSuffix->name.'*' : $this->name.' *'.$itemSuffix->name.'*';
        }

        return $itemName === '' ? $this->name : $itemName;
    }

    public function getAffixCountAttribute()
    {
        if (! is_null($this->item_prefix_id) && ! is_null($this->item_suffix_id)) {
            return 2;
        }
        if (! is_null($this->item_prefix_id) || ! is_null($this->item_suffix_id)) {
            return 1;
        }

        return 0;
    }

    public function getIsUniqueAttribute()
    {
        return $this->itemPrefix?->randomly_generated || $this->itemSuffix?->randomly_generated;
    }

    public function getRequiredMonsterAttribute()
    {
        return $this->type === 'quest' ? Monster::where('quest_item_id', $this->id)->with('gameMap')->first() : null;
    }

    public function getRequiredQuestAttribute()
    {
        return $this->type === 'quest' ? Quest::where('reward_item', $this->id)->with('npc', 'npc.gameMap', 'item')->first() : null;
    }

    public function getLocationsAttribute()
    {
        return $this->type === 'quest' ? Location::where('quest_reward_item_id', $this->id)->with('map')->get() : [];
    }

    public function getHolyStackDevouringDarknessAttribute()
    {
        return $this->appliedHolyStacks->sum('devouring_darkness_bonus') ?? 0.0;
    }

    public function getHolyStackStatBonusAttribute()
    {
        return $this->appliedHolyStacks->sum('stat_increase_bonus') ?? 0.0;
    }

    public function getHolyStacksAppliedAttribute()
    {
        return $this->appliedHolyStacks->count() ?? 0;
    }

    public function getAffixAttribute(string $attribute): float
    {
        $base = 0.0;

        if (! is_null($this->itemPrefix)) {
            $base += $this->itemPrefix->{$attribute};
        }

        if (! is_null($this->itemSuffix)) {
            $base += $this->itemSuffix->{$attribute};
        }

        return $base;
    }

    public function getSkillTrainingBonus(GameSkill $gameSkill): float
    {
        return $this->calculateTrainingBonus($this, $gameSkill);
    }

    public function getSkillBonus(GameSkill $gameSkill): float
    {
        return $this->calculateBonus($this, $gameSkill);
    }

    protected static function newFactory()
    {
        return ItemFactory::new();
    }
}
