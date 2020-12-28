<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Database\Factories\GameBuildingFactory;

class Building extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'game_building_id',
        'kingdoms_id',
        'level',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'level' => 'integer'
    ];

    public function gameBuilding() {
        return $this->belongsTo(GameBuilding::class);
    }

    public function kingdom() {
        return $this->belongsTo(Kingdom::class);
    }
}
