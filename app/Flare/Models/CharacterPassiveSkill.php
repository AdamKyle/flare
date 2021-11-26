<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\CharacterInCelestialFightFactory;

class CharacterPassiveSkill extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'passive_skill_id',
        'current_level',
        'hours_to_next',
        'started_at',
        'completed_at',
        'is_locked',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'current_level'  => 'integer',
        'hours_to_next'  => 'integer',
        'started_at'     => 'datetime',
        'completed_at'   => 'datetime',
        'is_locked'      => 'boolean',
    ];

    public function character() {
        return $this->belongsTo(Character::class);
    }

    public function passiveSkill() {
        return $this->belongsTo(PassiveSkill::class, 'passive_skill_id', 'id');
    }

}
