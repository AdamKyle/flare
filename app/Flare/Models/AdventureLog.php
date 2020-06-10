<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use App\Flare\Models\GameRace;
use App\Flare\Models\GameClass;
use App\Flare\Models\Skill;
use App\Flare\Models\Inventory;
use App\Flare\Models\EquippedItem;
use App\User;

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
        'completed',
        'last_completed_level',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'complete'             => 'boolean',
        'last_completed_level' => 'integer',
    ];

    public function character() {
        return $this->belongsTo(Character::class);
    }

    public function adventure() {
        return $this->hasOne(Adventure::class, 'id', 'adventure_id');
    }
}
