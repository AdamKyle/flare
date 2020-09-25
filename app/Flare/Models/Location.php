<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Flare\Models\GameMap;
use Database\Factories\LocationFactory;

class Location extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'game_map_id',
        'quest_reward_item_id',
        'description',
        'is_port',
        'x',
        'y',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'y'       => 'integer',
        'x'       => 'integer',
        'is_port' => 'boolean',
    ];

    public function questRewardItem() {
        return $this->hasOne(Item::class, 'id', 'quest_reward_item_id');
    }

    public function map() {
        return $this->hasOne(GameMap::class, 'id', 'game_map_id');
    }

    public function adventures() {
        return $this->belongsToMany(Adventure::class);
    }

    public static function dataTableSearch($query) {
        return empty($query) ? static::query()
            : static::where('name', 'like', '%'.$query.'%');
    }

    protected static function newFactory() {
        return LocationFactory::new();
    }
}
