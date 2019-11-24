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
        'name',
        'currently_training',
        'level',
        'xp',
        'xp_max',
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
        'xp'                    => 'integer',
        'xp_max'                => 'integer',
        'skill_bonus'           => 'integer',
        'skill_bonus_per_level' => 'integer',
    ];
}
