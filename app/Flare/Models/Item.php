<?php

namespace App\Flare\Models;

use App\Flare\Models\Traits\CalculateSkillBonus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Bkwld\Cloner\Cloneable;
use Database\Factories\ItemFactory;
use App\Flare\Models\Traits\WithSearch;

class Item extends Model
{

    use Cloneable;

    use HasFactory, WithSearch, CalculateSkillBonus;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'item_suffix_id',
        'item_prefix_id',
        'drop_location_id',
        'type',
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
        'base_damage_mod_bonus',
        'base_healing_mod_bonus',
        'base_ac_mod_bonus',
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
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'base_damage'                      => 'integer',
        'base_healing'                     => 'integer',
        'base_ac'                          => 'integer',
        'cost'                             => 'integer',
        'gold_dust_cost'                   => 'integer',
        'shards_cost'                      => 'integer',
        'copper_coin_cost'                 => 'integer',
        'parent_id'                        => 'integer',
        'holy_level'                       => 'integer',
        'holy_stacks'                      => 'integer',
        'base_damage_mod'                  => 'float',
        'base_healing_mod'                 => 'float',
        'base_ac_mod'                      => 'float',
        'str_mod'                          => 'float',
        'dur_mod'                          => 'float',
        'dex_mod'                          => 'float',
        'chr_mod'                          => 'float',
        'int_mod'                          => 'float',
        'agi_mod'                          => 'float',
        'focus_mod'                        => 'float',
        'skill_training_bonus'             => 'float',
        'skill_bonus'                      => 'float',
        'base_damage_mod_bonus'            => 'float',
        'base_healing_mod_bonus'           => 'float',
        'base_ac_mod_bonus'                => 'float',
        'fight_time_out_mod_bonus'         => 'float',
        'move_time_out_mod_bonus'          => 'float',
        'can_craft'                        => 'boolean',
        'can_resurrect'                    => 'boolean',
        'randomly_generated'               => 'boolean',
        'skill_level_required'             => 'integer',
        'skill_level_trivial'              => 'integer',
        'craft_only'                       => 'boolean',
        'can_drop'                         => 'boolean',
        'market_sellable'                  => 'boolean',
        'usable'                           => 'boolean',
        'damages_kingdoms'                 => 'boolean',
        'ignores_caps'                     => 'boolean',
        'stat_increase'                    => 'boolean',
        'can_use_on_other_items'           => 'boolean',
        'is_mythic'                        => 'boolean',
        'kingdom_damage'                   => 'float',
        'lasts_for'                        => 'integer',
        'increase_stat_by'                 => 'float',
        'affects_skill_type'               => 'integer',
        'increase_skill_bonus_by'          => 'float',
        'increase_skill_training_bonus_by' => 'float',
        'resurrection_chance'              => 'float',
        'spell_evasion'                    => 'float',
        'artifact_annulment'               => 'float',
        'healing_reduction'                => 'float',
        'affix_damage_reduction'           => 'float',
        'devouring_light'                  => 'float',
        'devouring_darkness'               => 'float',
        'xp_bonus'                         => 'float',
        'ambush_chance'                    => 'float',
        'ambush_resistance'                => 'float',
        'counter_chance'                   => 'float',
        'counter_resistance'               => 'float',
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

    public function inventorySlots() {
        return $this->hasMany(InventorySlot::class, 'item_id', 'id');
    }

    public function inventorySetSlots() {
        return $this->hasMany(SetSlot::class, 'item_id', 'id');
    }

    public function marketListings() {
        return $this->hasMany(MarketBoard::class, 'item_id', 'id');
    }

    public function marketHistory() {
        return $this->hasMany(MarketHistory::class, 'item_id', 'id');
    }

    public function itemSuffix() {
        return $this->hasOne(ItemAffix::class, 'id', 'item_suffix_id');
    }

    public function itemPrefix() {
        return $this->hasOne(ItemAffix::class, 'id', 'item_prefix_id');
    }

    public function appliedHolyStacks() {
        return $this->hasMany(HolyStack::class, 'item_id', 'id');
    }

    public function dropLocation() {
        return $this->hasOne(Location::class, 'id', 'drop_location_id')->with('map');
    }

    public function children() {
        return $this->hasMany($this, 'parent_id')->with('children');
    }

    public function parent() {
        return $this->belongsTo($this, 'parent_id');
    }

    /**
     * Gets the affix name attribute.
     *
     * When calling affix_name on the item, it will return the name with all affixes applied.
     */
    public function getAffixNameAttribute() {
        $itemPrefix = ItemAffix::find($this->item_prefix_id);
        $itemSuffix = ItemAffix::find($this->item_suffix_id);
        $itemName   = '';

        if (!is_null($itemPrefix)) {
            $itemName = '*'.$itemPrefix->name.'* ' . $this->name;
        }

        if (!is_null($itemSuffix)) {
            if ($itemName !== '') {
                $itemName .= ' *'.$itemSuffix->name.'*';
            } else {
                $itemName = $this->name . ' *'.$itemSuffix->name.'*';
            }
        }

        if ($itemName === '') {
            return $this->name;
        }

        return $itemName;
    }

    public function getAffixCountAttribute() {
        if (!is_null($this->item_prefix_id) && !is_null($this->item_suffix_id)) {
            return 2;
        }

        if (is_null($this->item_prefix_id) && !is_null($this->item_suffix_id)) {
            return 1;
        }

        if (!is_null($this->item_prefix_id) && is_null($this->item_suffix_id)) {
            return 1;
        }

        return 0;
    }

    public function getIsUniqueAttribute() {
        $value = false;

        if (!is_null($this->itemPrefix)) {
            $value = $this->itemPrefix->randomly_generated;
        }

        if ($value) {
            return $value;
        }

        if (!is_null($this->itemSuffix)) {
            $value = $this->itemSuffix->randomly_generated;
        }

        return $value;
    }

    public function getRequiredMonsterAttribute() {
        if ($this->type === 'quest') {
            return Monster::where('quest_item_id', $this->id)->with('gameMap')->first();
        }

        return null;
    }

    public function getRequiredQuestAttribute() {
        if ($this->type === 'quest') {
            return Quest::where('reward_item', $this->id)->with('npc', 'npc.gameMap', 'item')->first();
        }

        return null;
    }

    public function getLocationsAttribute() {
        if ($this->type === 'quest') {
           return Location::where('quest_reward_item_id', $this->id)->with('map')->get();
        }

        return [];
    }

    public function getHolyStackDevouringDarknessAttribute() {
        if ($this->appliedHolyStacks->isNotEmpty()) {
            return $this->appliedHolyStacks->sum('devouring_darkness_bonus');
        }

        return 0.0;
    }

    public function getHolyStackStatBonusAttribute() {
        if ($this->appliedHolyStacks->isNotEmpty()) {
            return $this->appliedHolyStacks->sum('stat_increase_bonus');
        }

        return 0.0;
    }

    public function getHolyStacksAppliedAttribute() {
        if ($this->appliedHolyStacks->isNotEmpty()) {
            return $this->appliedHolyStacks->count();
        }

        return 0;
    }

    /**
     * Gets the total damage value for the item.
     *
     * In some cases an item might not have a base_damage value.
     * however, might have either prefix or suffix or both.
     *
     * In this case we will set the damage variable to one.
     * this will allow the damage modifiers to be applied to the item.
     *
     * Which in turns allows the player to their total damage increased when
     * attacking.
     *
     * @return int.
     */
    public function scopeGetTotalDamage(): int {
        $baseDamage = is_null($this->base_damage) ? 0 : $this->base_damage;
        $damage     = $baseDamage;

        if (!is_null($this->itemPrefix)) {
            $damage += ($damage * $this->itemPrefix->base_damage_mod);
        }

        if (!is_null($this->itemSuffix)) {
            $damage += ($damage * $this->itemSuffix->base_damage_mod);
        }

        if (!is_null($this->base_damage_mod)) {
            $damage += ($damage * $this->base_damage_mod);
        }

        return round($damage);
    }

    /**
     * Gets the total defence value for the item.
     *
     * In some cases an item might not have a base_ac value.
     * however, might have either prefix or suffix or both.
     *
     * In this case we will set the ac variable to one.
     * this will allow the ac modifiers to be applied to the item.
     *
     * Which in turns allows the player to their total ac increased when
     * defending from attacks.
     *
     * @return int.
     */
    public function scopeGetTotalDefence(): int {
        $baseAc = is_null($this->base_ac) ? 0 : $this->base_ac;
        $ac     = $baseAc;

        if (!is_null($this->itemPrefix)) {
            $ac += $ac * $this->itemPrefix->base_ac_mod;
        }

        if (!is_null($this->itemSuffix)) {
            $ac += $ac * $this->itemSuffix->base_ac_mod;
        }

        $ac += $ac * $this->base_ac_mod;

        return ceil($ac);
    }

    /**
     * Gets the total healing value for the item.
     *
     * In some cases an item might not have a base_healing value,
     * however, it might have either prefix or suffix or both.
     *
     * In this case we will set the healFor variable to one.
     * this will allow the healing modifiers to be applied to the item.
     *
     * Which in turns allows the player to their total healing increased when
     * attacking.
     *
     * @return int.
     */
    public function scopeGetTotalHealing(): int {
        $baseHealing = is_null($this->base_healing) ? 0 : $this->base_healing;
        $healFor     = $baseHealing;

        if (!is_null($this->itemPrefix)) {
            $healFor += ($healFor * $this->itemPrefix->base_healing_mod);
        }

        if (!is_null($this->itemSuffix)) {
            $healFor += ($healFor * $this->itemSuffix->base_healing_mod);
        }

        return ceil($healFor);
    }

    /**
     * @return float
     */
    public function scopeGetTotalFightTimeOutMod(): float {
        return is_null($this->fight_time_out_mod_bonus) ? 0.0 : $this->fight_time_out_mod_bonus;
    }

    /**
     * Get the total Base Damage Mode
     *
     * @return float
     */
    public function scopeGetTotalBaseDamageMod(): float {
        return is_null($this->base_damage_mod_bonus) ? 0.0 : $this->base_damage_mod_bonus;
    }

    /**
     * Gets the total percentage increase for a stat.
     *
     * @return float
     */
    public function getTotalPercentageForStat(string $stat): float {
        $baseStat = is_null($this->{$stat . '_mod'}) ? 0.0 : $this->{$stat . '_mod'};

        if (!is_null($this->itemPrefix)) {
            $statBonus  = $this->itemPrefix->{$stat . '_mod'};
            $baseStat  += !is_null($statBonus) ? $statBonus : 0.0;
        }

        if (!is_null($this->itemSuffix)) {
            $statBonus = $this->itemSuffix->{$stat . '_mod'};
            $baseStat += !is_null($statBonus) ? $statBonus : 0.0;
        }

        if ($this->holy_stack_stat_bonus > 0) {
            $baseStat += $this->holy_stack_stat_bonus;
        }

        return number_format($baseStat, 2);
    }

    /**
     * Gets the total skill training bonus (XP bonus)
     *
     * @param string $skillName
     * @return float
     */
    public function getSkillTrainingBonus(GameSkill $gameSkill): float {
        return $this->calculateTrainingBonus($this, $gameSkill);
    }

    public function scopeGetItemSkills($query): array {
        $skills = [];

        if (!is_null($this->itemPrefix)) {
            if (!is_null($this->itemPrefix->skill_name)) {
                $skills[] = [
                    'skill_name'           => $this->itemPrefix->skill_name,
                    'skill_training_bonus' => $this->itemPrefix->skill_training_bonus,
                    'skill_bonus'          => $this->itemPrefix->skill_bonus,
                ];
            }
        }

        if (!is_null($this->itemSuffix)) {
            if (!is_null($this->itemSuffix->skill_name)) {
                $skills[] = [
                    'skill_name'           => $this->itemSuffix->skill_name,
                    'skill_training_bonus' => $this->itemSuffix->skill_training_bonus,
                    'skill_bonus'          => $this->itemSuffix->skill_bonus,
                ];
            }
        }

        if (!is_null($this->skill_name)) {
            $skills[] = [
                'skill_name'           => $this->skill_name,
                'skill_training_bonus' => $this->skill_training_bonus,
                'skill_bonus'          => $this->skill_bonus,
            ];
        }

        return $skills;
    }

    /**
     * Gets the total skill training bonus (Bonus when using)
     *
     * @param string $skillName
     * @return float
     */
    public function getSkillBonus(GameSkill $gameSkill): float {
        return $this->calculateBonus($this, $gameSkill);
    }

    protected static function newFactory() {
        return ItemFactory::new();
    }
}
