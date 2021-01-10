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
        'max_stone',
        'max_wood',
        'max_clay',
        'max_iron',
        'current_stone',
        'current_wood',
        'current_clay',
        'current_iron',
        'current_population',
        'max_population',
        'x_position',
        'y_position',
        'current_morale',
        'max_morale',
        'treasury',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'color'              => 'array',
        'max_stone'          => 'integer',
        'max_wood'           => 'integer',
        'max_clay'           => 'integer',
        'max_iron'           => 'integer',
        'current_stone'      => 'integer',
        'current_wood'       => 'integer',
        'current_clay'       => 'integer',
        'current_iron'       => 'integer',
        'current_population' => 'integer',
        'max_population'     => 'integer',
        'x_position'         => 'integer',
        'y_position'         => 'integer',
        'current_morale'     => 'float',
        'max_morale'         => 'float',
        'treasury'           => 'integer',
    ];

    public function buildings() {
        return $this->hasMany(Building::class, 'kingdoms_id', 'id');
    }

    public function buildingsQueue() {
        return $this->hasMany(BuildingInQueue::class, 'kingdom_id', 'id');
    }

    public function character() {
        return $this->belongsTo(Character::class, 'character_id', 'id');
    }

    protected static function newFactory() {
        return KingdomFactory::new();
    }
}
