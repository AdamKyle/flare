<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class FactionLoyalty extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'faction_id',
        'character_id',
    ];

    public function faction() {
        return $this->belongsTo(Faction::class, 'faction_id', 'id');
    }

    public function character() {
        return $this->belongsTo(Character::class, 'character_id', 'id');
    }
}
