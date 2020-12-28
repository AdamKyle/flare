<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Flare\Models\Traits\WithSearch;
use Database\Factories\KingdomFactory;

class Kingdom extends Model
{

    use HasFactory, WithSearch;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'game_map_id',
        'name',
        'color',
        'stone',
        'wood',
        'clay',
        'iron',
        'current_population',
        'max_population',
        'x_position',
        'y_position',
        'morale',
        'treasury',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'color'              => 'array',
        'stone'              => 'integer',
        'wood'               => 'integer',
        'clay'               => 'integer',
        'iron'               => 'integer',
        'current_population' => 'integer',
        'max_population'     => 'integer',
        'x_position'         => 'integer',
        'y_position'         => 'integer',
        'morale'             => 'float',
        'treasury'           => 'integer',
    ];

    protected static function newFactory() {
        return KingdomFactory::new();
    }
}
