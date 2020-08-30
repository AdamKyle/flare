<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class AdventureLog extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'adventure_id',
        'complete',
        'in_progress',
        'last_completed_level',
        'logs',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'logs'                 => 'array',
        'complete'             => 'boolean',
        'in_progress'          => 'boolean',
        'last_completed_level' => 'integer',
    ];

    public function character() {
        return $this->belongsTo(Character::class);
    }

    public function adventure() {
        return $this->hasOne(Adventure::class, 'id', 'adventure_id');
    }
}
