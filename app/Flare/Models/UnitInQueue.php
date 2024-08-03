<?php

namespace App\Flare\Models;

use Database\Factories\UnitInQueueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'gold_paid',
        'completed_at',
        'started_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'integer',
        'gold_paid' => 'integer',
        'completed_at' => 'datetime',
        'started_at' => 'datetime',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }

    public function unit()
    {
        return $this->belongsTo(GameUnit::class, 'game_unit_id', 'id');
    }

    public function kingdom()
    {
        return $this->belongsTo(Kingdom::class);
    }

    protected static function newFactory()
    {
        return UnitInQueueFactory::new();
    }
}
