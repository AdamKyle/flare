<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\BuildingInQueueFactory;

class BuildingInQueue extends Model
{

    use HasFactory;

    protected $table = 'buildings_in_queue';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'kingdom_id',
        'building_id',
        'to_level',
        'completed_at',
        'started_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'to_level'     => 'integer',
        'completed_at' => 'datetime',
        'started_at'   => 'datetime',
    ];

    public function character() {
        return $this->belongsTo(Character::class);
    }

    public function building() {
        return $this->belongsTo(KingdomBuilding::class);
    }

    public function kingdom() {
        return $this->belongsTo(Kingdom::class);
    }

    protected static function newFactory() {
        return BuildingInQueueFactory::new();
    }
}
