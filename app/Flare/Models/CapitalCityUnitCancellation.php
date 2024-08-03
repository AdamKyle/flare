<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CapitalCityUnitCancellation extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id',
        'kingdom_id',
        'character_id',
        'capital_city_unit_queue_id',
        'status',
        'travel_time_completed_at',
        'request_kingdom_id',
    ];

    protected $casts = [
        'travel_time_completed_at' => 'datetime',
    ];

    public function unit(): HasOne
    {
        return $this->hasOne(GameUnit::class, 'id', 'unit_id');
    }

    public function kingdom(): HasOne
    {
        return $this->hasOne(Kingdom::class, 'id', 'kingdom_id');
    }

    public function character(): HasOne
    {
        return $this->hasOne(Character::class, 'id', 'character_id');
    }

    public function capitalCityUnitQueue(): HasOne
    {
        return $this->hasOne(CapitalCityUnitQueue::class, 'id', 'capital_city_unit_queue_id');
    }
}
