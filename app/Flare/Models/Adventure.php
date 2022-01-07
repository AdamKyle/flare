<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\AdventureFactory;
use App\Flare\Models\Traits\WithSearch;

class Adventure extends Model
{

    use HasFactory, WithSearch;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'reward_item_id',
        'over_flow_set_id',
        'levels',
        'time_per_level',
        'gold_rush_chance',
        'item_find_chance',
        'skill_exp_bonus',
        'exp_bonus',
        'published',
        'location_id',
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
        'exp_bonus'        => 'float',
        'published'        => 'boolean',
    ];

    public function monsters() {
        return $this->belongsToMany(Monster::class);
    }

    public function location() {
        return $this->hasOne(Location::class, 'id', 'location_id');
    }

    public function itemReward() {
        return $this->hasOne(Item::class, 'id', 'reward_item_id');
    }

    public function overflowSet() {
        return $this->hasOne(InventorySet::class, 'id', 'over_flow_set_id');
    }

    public function floorDescriptions() {
        return $this->hasMany(AdventureFloorDescriptions::class);
    }

    protected static function newFactory() {
        return AdventureFactory::new();
    }
}
