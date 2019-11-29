<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

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
}
