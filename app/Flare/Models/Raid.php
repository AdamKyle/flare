<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class Raid extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'story',
        'raid_boss_id',
        'raid_monster_ids',
        'raid_boss_location_id',
        'corrupted_location_ids',
    ];

    protected $casts = [
        'raid_monster_ids'       => 'array',
        'corrupted_location_ids' => 'array',
    ];

    public function raidBoss() {
        return $this->hasOne(Monster::class, 'id', 'raid_boss_id');
    }

    public function raidBossLocation() {
        return $this->hasOne(Location::class, 'id', 'raid_boss_location_id');
    }
}
