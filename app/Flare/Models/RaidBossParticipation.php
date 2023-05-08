<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class RaidBossParticipation extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'raid_boss_id',
        'damage_dealt',
    ];

    protected $casts = [
        'character_id'    => 'integer',
        'raid_boss_id'    => 'integer',
        'damage_dealt'    => 'integer',
    ];

    public function character() {
        return $this->hasOne(Character::class, 'id', 'character_id');
    }

    public function raidBoss() {
        return $this->hasOne(Monster::class, 'id', 'raid_boss_id');
    }
}
