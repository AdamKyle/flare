<?php

namespace App\Flare\Models;

use Database\Factories\KingdomLogFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KingdomLog extends Model {

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'attacking_character_id',
        'from_kingdom_id',
        'to_kingdom_id',
        'status',
        'units_sent',
        'units_survived',
        'old_buildings',
        'new_buildings',
        'old_units',
        'new_units',
        'item_damage',
        'morale_loss',
        'published',
        'opened',
        'created_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'units_sent'     => 'array',
        'units_survived' => 'array',
        'old_buildings'  => 'array',
        'new_buildings'  => 'array',
        'old_units'      => 'array',
        'new_units'      => 'array',
        'published'      => 'boolean',
        'opened'         => 'boolean',
        'item_damage'    => 'float',
        'morale_loss'    => 'float',
        'status'         => 'integer',
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

    public function setUnitsSentAttribute($value) {
        $this->attributes['units_sent'] = json_encode($value);
    }

    public function setUnitsSurvivedAttribute($value) {
        $this->attributes['units_survived'] = json_encode($value);
    }

    public function setOldBuildingsAttribute($value) {
        $this->attributes['old_buildings'] = json_encode($value);
    }

    public function setNewBuildingsUnitsAttribute($value) {
        $this->attributes['new_buildings'] = json_encode($value);
    }

    public function setOldUnitsAttribute($value) {
        $this->attributes['old_units'] = json_encode($value);
    }

    public function setNewUnitsUnitsAttribute($value) {
        $this->attributes['new_units'] = json_encode($value);
    }

    protected static function newFactory() {
        return new KingdomLogFactory();
    }
}
