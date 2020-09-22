<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use App\Flare\Models\Map;

class GameMap extends Model
{
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

    public static function dataTableSearch($query) {
        return empty($query) ? static::query()
            : static::where('name', 'like', '%'.$query.'%');
    }
}
