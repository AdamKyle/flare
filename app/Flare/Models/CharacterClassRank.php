<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterClassRank extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'game_class_id',
        'current_xp',
        'required_xp',
        'level',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'current_xp'   => 'integer',
        'required_xp'  => 'integer',
        'level'        => 'integer',
    ];

    public function character() {
        return $this->belongsTo(Character::class, 'character_id', 'id');
    }

    public function gameClass() {
        return $this->belongsTo(GameClass::class, 'game_class_id', 'id');
    }

    public function weaponMasteries() {
        return $this->hasMany(CharacterClassRankWeaponMastery::class);
    }
}
