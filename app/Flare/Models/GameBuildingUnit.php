<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\GameBuildingUnitFactory;

class GameBuildingUnit extends Model {

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'game_building_id',
        'game_unit_id',
        'required_level',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'required_level' => 'integer',
    ];

    protected $appends = [
        'building_name'
    ];

    public function gameBuilding() {
        return $this->belongsTo(GameBuilding::class);
    }

    public function gameUnit() {
        return $this->hasOne(GameUnit::class, 'id', 'game_unit_id');
    }

    public function getBuildingNameAttribute() {
        return $this->gameBuilding->name;
    }

    protected static function newFactory() {
        return GameBuildingUnitFactory::new();
    }
}
