<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use App\Flare\Models\GameRace;
use App\Flare\Models\GameClass;
use App\Flare\Models\Skill;
use App\Flare\Models\Inventory;
use App\Flare\Models\EquippedItem;
use App\User;

class Character extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'name',
        'damage_stat',
        'game_race_id',
        'game_class_id',
        'inventory_max',
        'can_attack',
        'can_move',
        'can_craft',
        'is_dead',
        'can_move_again_at',
        'can_attack_again_at',
        'can_craft_again_at',
        'level',
        'xp',
        'xp_next',
        'str',
        'dur',
        'dex',
        'chr',
        'int',
        'ac',
        'gold',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'inventory_max'       => 'integer',
        'can_attack'          => 'boolean',
        'can_move'            => 'boolean',
        'can_craft'           => 'boolean',
        'is_dead'             => 'boolean',
        'can_move_again_at'   => 'datetime',
        'can_attack_again_at' => 'datetime',
        'can_craft_again_at'  => 'datetime',
        'level'               => 'integer',
        'xp'                  => 'integer',
        'xp_next'             => 'integer',
        'str'                 => 'integer',
        'dur'                 => 'integer',
        'dex'                 => 'integer',
        'chr'                 => 'integer',
        'int'                 => 'integer',
        'ac'                  => 'integer',
        'gold'                => 'integer',
    ];

    public function race() {
        return $this->belongsTo(GameRace::class, 'game_race_id', 'id');
    }

    public function class() {
        return $this->belongsTo(GameClass::class, 'game_class_id', 'id');
    }

    public function skills() {
        return $this->hasMany(Skill::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function inventory() {
        return $this->hasOne(Inventory::class, 'character_id', 'id');
    }

    public function map() {
        return $this->hasOne(Map::class);
    }

    public function adventureLogs() {
        return $this->hasMany(AdventureLog::class, 'character_id', 'id');
    }
}
