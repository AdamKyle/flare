<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class RaidBoss extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'raid_id',
        'raid_boss_id',
        'boss_max_hp',
        'boss_current_hp',
    ];

    protected $casts = [
        'raid_id'         => 'integer',
        'raid_boss_id'    => 'integer',
        'boss_max_hp'     => 'integer',
        'boss_current_hp' => 'integer',
    ];

    public function raid() {
        return $this->hasOne(Raid::class, 'id', 'raid_id');
    }

    public function raidBoss() {
        return $this->hasOne(Monster::class, 'id', 'raid_boss_id');
    }
}
