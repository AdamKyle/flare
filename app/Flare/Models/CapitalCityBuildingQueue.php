<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\UnitInQueueFactory;

class CapitalCityBuildingQueue extends Model {

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'kingdom_id',
        'building_request_data',
        'messages',
        'completed_at',
        'started_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'building_request_data' => 'array',
        'messages' => 'array',
        'completed_at' => 'datetime',
        'started_at'   => 'datetime',
    ];

    public function character() {
        return $this->belongsTo(Character::class);
    }

    public function kingdom() {
        return $this->belongsTo(Kingdom::class);
    }
}
