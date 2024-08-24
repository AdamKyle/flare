<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KingdomBuildingExpansion extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'kingdom_building_id',
        'kingdom_id',
        'expansion_type',
        'expansion_count',
        'expansions_left',
        'minutes_until_next_expansion',
        'resource_costs',
        'gold_bars_cost',
        'resource_increases',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'expansion_type' => 'integer',
        'expansion_count' => 'integer',
        'expansions_left' => 'integer',
        'minutes_until_next_expansion' => 'integer',
        'resource_costs' => 'array',
        'gold_bars_cost' => 'integer',
        'resource_increases' => 'array',
    ];

    public function kingdomBuilding()
    {
        return $this->belongsTo(KingdomBuilding::class, 'kingdom_building_id', 'id');
    }

    public function kingdom()
    {
        return $this->belongsTo(Kingdom::class, 'kingdom_id', 'id');
    }
}
