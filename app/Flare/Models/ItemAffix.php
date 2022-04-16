<?php

namespace App\Flare\Models;

use Bkwld\Cloner\Cloneable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\ItemAffixFactory;
use App\Flare\Models\Traits\WithSearch;

class ItemAffix extends Model
{
    use HasFactory, WithSearch, Cloneable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'base_damage_mod',
        'base_ac_mod',
        'type',
        'description',
        'base_healing_mod',
        'str_mod',
        'dur_mod',
        'dex_mod',
        'chr_mod',
        'int_mod',
        'agi_mod',
        'focus_mod',
        'str_reduction',
        'dur_reduction',
        'dex_reduction',
        'chr_reduction',
        'int_reduction',
        'agi_reduction',
        'focus_reduction',
        'reduces_enemy_stats',
        'steal_life_amount',
        'entranced_chance',
        'damage',
        'irresistible_damage',
        'damage_can_stack',
        'class_bonus',
        'cost',
        'skill_name',
        'skill_training_bonus',
        'skill_bonus',
        'skill_reduction',
        'resistance_reduction',
        'int_required',
        'skill_level_required',
        'skill_level_trivial',
        'affects_skill_type',
        'base_damage_mod_bonus',
        'base_healing_mod_bonus',
        'base_ac_mod_bonus',
        'fight_time_out_mod_bonus',
        'move_time_out_mod_bonus',
        'devouring_light',
        'can_drop',
        'randomly_generated',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'base_damage_mod'                  => 'float',
        'base_healing_mod'                 => 'float',
        'str_mod'                          => 'float',
        'dur_mod'                          => 'float',
        'dex_mod'                          => 'float',
        'chr_mod'                          => 'float',
        'int_mod'                          => 'float',
        'agi_mod'                          => 'float',
        'focus_mod'                        => 'float',
        'str_reduction'                    => 'float',
        'dur_reduction'                    => 'float',
        'dex_reduction'                    => 'float',
        'chr_reduction'                    => 'float',
        'int_reduction'                    => 'float',
        'agi_reduction'                    => 'float',
        'focus_reduction'                  => 'float',
        'reduces_enemy_stats'              => 'float',
        'steal_life_amount'                => 'float',
        'entranced_chance'                 => 'float',
        'skill_training_bonus'             => 'float',
        'skill_bonus'                      => 'float',
        'skill_reduction'                  => 'float',
        'resistance_reduction'             => 'float',
        'affects_skill_type'               => 'float',
        'base_damage_mod_bonus'            => 'float',
        'base_healing_mod_bonus'           => 'float',
        'base_ac_mod_bonus'                => 'float',
        'fight_time_out_mod_bonus'         => 'float',
        'move_time_out_mod_bonus'          => 'float',
        'class_bonus'                      => 'float',
        'devouring_light'                  => 'float',
        'cost'                             => 'integer',
        'int_required'                     => 'integer',
        'skill_level_required'             => 'integer',
        'skill_level_trivial'              => 'integer',
        'damage'                           => 'integer',
        'can_drop'                         => 'boolean',
        'irresistible_damage'              => 'boolean',
        'damage_can_stack'                 => 'boolean',
        'randomly_generated'               => 'boolean',
    ];

    public function itemsWithPrefix() {
        return $this->hasMany(Item::class, 'item_prefix_id', 'id');
    }

    public function itemsWithSuffix() {
        return $this->hasMany(Item::class, 'item_suffix_id', 'id');
    }

    protected static function newFactory() {
        return ItemAffixFactory::new();
    }

    public function scopeGetOppositeType() {
        if ($this->type === 'suffix')  {
            return 'prefix';
        }

        return 'suffix';
    }
}
