<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\UnitInQueueFactory;

class UnitInQueue extends Model
{

    use HasFactory;

    protected $table = 'units_in_queue';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'kingdom_id',
        'game_unit_id',
        'amount',
        'completed_at',
        'started_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'amount'       => 'integer',
        'completed_at' => 'datetime',
        'started_at'   => 'datetime',
    ];

    public function getCharacter() {
        return $this->belongsTo(Character::class);
    }

    public function unit() {
        return $this->belongsTo(GameUnit::class);
    }

    public function kingdom() {
        return $this->belongsTo(Kingdom::class);
    }

    protected static function newFactory() {
        return UnitInQueueFactory::new();
    }
}
