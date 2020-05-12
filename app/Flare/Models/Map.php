<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use App\Admin\Models\GameMap;

class Map extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'position_x',
        'position_y',
        'character_position_x',
        'character_position_y',
        'game_map_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'position_x'           => 'integer',
        'position_y'           => 'integer',
        'character_position_x' => 'integer',
        'character_position_y' => 'integer',
    ];

    public function gameMap() {
        return $this->hasOne(GameMap::class, 'id', 'game_map_id');
    }
}
