<?php

namespace App\Flare\Models;

use Database\Factories\MapFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Map extends Model
{
    use HasFactory;

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
        'position_x' => 'integer',
        'position_y' => 'integer',
        'character_position_x' => 'integer',
        'character_position_y' => 'integer',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class, 'character_id', 'id');
    }

    public function gameMap()
    {
        return $this->belongsTo(GameMap::class, 'game_map_id', 'id');
    }

    protected static function newFactory()
    {
        return MapFactory::new();
    }
}
