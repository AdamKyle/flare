<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Flare\Models\Traits\WithSearch;
use Database\Factories\KingdomUnitFactory;

class KingdomUnit extends Model
{

    use HasFactory, WithSearch;

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

    public function kingdom() {
        return $this->belongsTo(Kingdom::class, 'id', 'kingdom_id');
    }

    public function gameUnit() {
        return $this->hasOne(GameUnit::class);
    }

    protected static function newFactory() {
        return KingdomUnitFactory::new();
    }
}
