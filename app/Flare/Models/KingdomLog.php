<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\KingdomBuildingFactory;

class KingdomLog extends Model
{

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'from_kingdom_id',
        'to_kingdom_id',
        'status',
        'units_sent',
        'units_survived',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'units_sent'         => 'array',
        'units_survived'     => 'array',
    ];

    protected $appends = [
        'from_kingdom',
        'to_kingdom',
    ];

    public function getFromKingdomAttribute() {
        return Kingdom::find($this->from_kingdom_id);
    }

    public function getToKingdomAttribute() {
        return Kingdom::find($this->to_kingdom_id);
    }

    public function setUnitsSentAttribute($value) {
        $this->attributes['units_sent'] = json_encode($value);
    }

    public function setUnitsSurvivedAttribute($value) {
        $this->attributes['units_survived'] = json_encode($value);
    }

    protected static function newFactory() {
        return KingdomBuildingFactory::new();
    }
}
