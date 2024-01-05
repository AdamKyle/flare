<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\ItemAffixFactory;
use Bkwld\Cloner\Cloneable;

class ItemAffix extends Model {

    use HasFactory, Cloneable;

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
        'damage_amount',
        'irresistible_damage',
        'damage_can_stack',
        'cost',
        'skill_name',
        'skill_training_bonus',
        'skill_bonus',
        'skill_reduction',
        'resistance_reduction',
        'int_required',
        'skill_level_required',
        'skill_level_trivial',
        'devouring_light',
        'can_drop',
        'randomly_generated',
        'affix_type',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
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
        'devouring_light'                  => 'float',
        'damage_amount'                    => 'float',
        'cost'                             => 'integer',
        'int_required'                     => 'integer',
        'skill_level_required'             => 'integer',
        'skill_level_trivial'              => 'integer',
        'affix_type'                       => 'integer',
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
