<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class CelestialFight extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'monster_id',
        'started_at',
        'x_position',
        'y_position',
        'damaged_kingdom',
        'stole_treasury',
        'weakened_morale',
        'current_health',
        'max_health',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'started_at'      => 'dateTime',
        'x_position'      => 'integer',
        'y_position'      => 'integer',
        'damaged_kingdom' => 'boolean',
        'stole_treasury'  => 'boolean',
        'weakened_morale' => 'boolean',
        'current_health'  => 'boolean',
        'max_health'      => 'boolean',
    ];

    public function monster() {
        return $this->belongsTo(Monster::class);
    }
}
