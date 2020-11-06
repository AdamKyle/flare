<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use App\Flare\Models\Map;
use App\Flare\Models\Traits\WithSearch;

class GameMap extends Model
{

    use WithSearch;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'path',
        'default',
    ];

    protected $casts = [
        'default' => 'boolean',
    ];

    public function maps() {
        return $this->hasMany(Map::class, 'game_map_id', 'id');
    }
}
