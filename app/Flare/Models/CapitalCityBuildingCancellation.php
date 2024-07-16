<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CapitalCityBuildingCancellation extends Model
{
    use HasFactory;

    protected $fillable = [
        'building_id',
        'kingdom_id',
        'character_id',
        'capital_city_building_queue_id',
        'status',
        'travel_time_completed_at',
    ];

    protected $casts = [
        'travel_time_completed_at' => 'datetime',
    ];

    public function building(): HasOne {
        return $this->hasOne(KingdomBuilding::class, 'id', 'building_id');
    }

    public function kingdom(): HasOne {
        return $this->hasOne(Kingdom::class, 'id', 'kingdom_id');
    }

    public function character(): HasOne {
        return $this->hasOne(Character::class, 'id', 'character_id');
    }

    public function capitalCityBuildingQueue(): HasOne {
        return $this->hasOne(CapitalCityBuildingQueue::class, 'id', 'capital_city_building_queue_id');
    }
}
