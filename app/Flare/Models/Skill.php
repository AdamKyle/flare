<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'monster_id',
        'description',
        'name',
        'currently_training',
        'level',
        'max_level',
        'xp',
        'xp_max',
        'xp_towards',
        'base_damage_mod',
        'base_healing_mod',
        'base_ac_mod',
        'fight_time_out_mod',
        'move_time_out_mod',
        'can_train',
        'skill_bonus',
        'skill_bonus_per_level',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'currently_training'    => 'boolean',
        'level'                 => 'integer',
        'max_level'             => 'integer',
        'xp'                    => 'integer',
        'xp_max'                => 'integer',
        'xp_towards'            => 'float',
        'base_damage_mod'       => 'float',
        'base_healing_mod'      => 'float',
        'base_ac_mod'           => 'float',
        'fight_time_out_mod'    => 'float',
        'move_time_out_mod'     => 'float',
        'can_train'             => 'boolean',
        'skill_bonus'           => 'float',
        'skill_bonus_per_level' => 'float',
    ];

    public function character() {
        return $this->belongsTo(Character::class);
    }
}
