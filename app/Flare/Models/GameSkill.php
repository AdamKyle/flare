<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\GameSkillFactory;

class GameSkill extends Model
{

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'description',
        'name',
        'max_level',
        'base_damage_mod_bonus_per_level',
        'base_healing_mod_bonus_per_level',
        'base_ac_mod_bonus_per_level',
        'fight_time_out_mod_bonus_per_level',
        'move_time_out_mod_bonus_per_level',
        'can_train',
        'skill_bonus_per_level',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'base_damage_mod_bonus_per_level'    => 'float',
        'base_healing_mod_bonus_per_level'   => 'float',
        'base_ac_mod_bonus_per_level'        => 'float',
        'fight_time_out_mod_bonus_per_level' => 'float',
        'move_time_out_mod_bonus_per_level'  => 'float',
        'skill_bonus_per_level'              => 'float',
        'can_train'                          => 'boolean',
    ];

    public static function dataTableSearch($query) {
        return empty($query) ? static::query()
            : static::where('name', 'like', '%'.$query.'%');
    }

    protected static function newFactory() {
        return GameSkillFactory::new();
    }
}
