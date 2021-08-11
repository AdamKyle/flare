<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\ItemAffixFactory;
use App\Flare\Models\Traits\WithSearch;

class ItemAffix extends Model
{
    use HasFactory, WithSearch;

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
        'cost',
        'skill_name',
        'skill_training_bonus',
        'skill_bonus',
        'int_required',
        'skill_level_required',
        'skill_level_trivial',
        'affects_skill_type',
        'base_damage_mod_bonus',
        'base_healing_mod_bonus',
        'base_ac_mod_bonus',
        'fight_time_out_mod_bonus',
        'move_time_out_mod_bonus',
        'can_drop',
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
        'skill_training_bonus'             => 'float',
        'skill_bonus'                      => 'float',
        'affects_skill_type'               => 'float',
        'base_damage_mod_bonus'            => 'float',
        'base_healing_mod_bonus'           => 'float',
        'base_ac_mod_bonus'                => 'float',
        'fight_time_out_mod_bonus'         => 'float',
        'move_time_out_mod_bonus'          => 'float',
        'cost'                             => 'integer',
        'int_required'                     => 'integer',
        'skill_level_required'             => 'integer',
        'skill_level_trivial'              => 'integer',
        'can_drop'                         => 'boolean',
    ];

    protected static function newFactory() {
        return ItemAffixFactory::new();
    }

    public function scopeGetOppisiteType() {
        if ($this->type === 'suffix')  {
            return 'prefix';
        }

        return 'suffix';
    }
}
