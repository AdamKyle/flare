<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class RaidBossParticipation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'raid_id',
        'attacks_left',
        'damage_dealt',
        'killed_boss',
    ];

    protected $casts = [
        'attacks_left' => 'integer',
        'damage_dealt' => 'integer',
        'killed_boss' => 'boolean',
    ];

    public function raid()
    {
        return $this->hasOne(Raid::class, 'id', 'raid_id');
    }

    public function character()
    {
        return $this->hasOne(Character::class, 'id', 'character_id');
    }
}
