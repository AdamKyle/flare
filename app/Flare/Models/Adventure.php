<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use App\Flare\Models\GameRace;
use App\Flare\Models\GameClass;
use App\Flare\Models\Skill;
use App\Flare\Models\Inventory;
use App\Flare\Models\EquippedItem;
use App\User;

class Adventure extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'location_id',
        'name',
        'description',
        'reward_item_id',
        'levels',
        'time_per_level',
        'gold_rush_chance',
        'item_find_chance',
        'skill_exp_bonus',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'levels'           => 'integer',
        'time_per_level'   => 'integer',
        'gold_rush_chance' => 'float',
        'item_find_chance' => 'float',
        'skill_exp_bonus'  => 'float',
    ];

    public function monsters() {
        return $this->hasMany(Monster::class);
    }

    public function location() {
        return $this->belongsTo(Location::class, 'location_id', 'id');
    }
}
