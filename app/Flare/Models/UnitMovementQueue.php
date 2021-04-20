<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\UnitInQueueFactory;
use Database\Factories\UnitMoveQueueFactory;

class UnitMovementQueue extends Model
{

    use HasFactory;

    protected $table = 'unit_movement_queue';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'from_kingdom_id',
        'to_kingdom_id',
        'units_moving',
        'completed_at',
        'started_at',
        'moving_to_x',
        'moving_to_y',
        'from_x',
        'from_y',
        'is_recalled',
        'is_returning',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'units_moving' => 'array',
        'completed_at' => 'datetime',
        'started_at'   => 'datetime',
        'is_recalled'  => 'boolean',
        'is_returning' => 'boolean',
        'moving_to_x'  => 'integer',
        'moving_to_y'  => 'integer',
        'from_x'       => 'integer',
        'from_y'       => 'integer',

    ];

    protected $appends = [
        'from_kingdom',
        'to_kingdom',
    ];

    public function character() {
        return $this->belongsTo(Character::class);
    }

    public function getFromKingdomAttribute() {
        return Kingdom::find($this->from_kingdom_id);
    }

    public function getToKingdomAttribute() {
        return Kingdom::find($this->to_kingdom_id);
    }

    public function setUnitsMovingAttribute($value) {
        $this->attributes['units_moving'] = json_encode($value);
    }

    protected static function newFactory() {
        return UnitMoveQueueFactory::new();
    }
}
