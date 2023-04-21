<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class SmeltingProgress extends Model {

    protected $table = 'smelting_progress';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'kingdom_id',
        'started_at',
        'completed_at',
        'amount_to_smelt',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'started_at'      => 'datetime',
        'completed_at'    => 'datetime',
        'amount_to_smelt' => 'integer',
    ];

    public function character() {
        return $this->belongsTo(Character::class, 'character_id', 'id');
    }

    public function kingdom() {
        return $this->belongsTo(Kingdom::class, 'kingdom_id', 'id');
    }
}
