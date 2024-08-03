<?php

namespace App\Flare\Models;

use Database\Factories\KingdomUnitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KingdomUnit extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'kingdom_id',
        'game_unit_id',
        'amount',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'integer',
    ];

    public function kingdom()
    {
        return $this->belongsTo(Kingdom::class, 'kingdom_id', 'id');
    }

    public function gameUnit()
    {
        return $this->hasOne(GameUnit::class, 'id', 'game_unit_id');
    }

    protected static function newFactory()
    {
        return KingdomUnitFactory::new();
    }
}
