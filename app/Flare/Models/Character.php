<?php

namespace App\Flare\Models;

use App\Flare\Builders\CharacterInformationBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Flare\Models\GameRace;
use App\Flare\Models\GameClass;
use App\Flare\Models\Skill;
use App\Flare\Models\Inventory;
use App\Flare\Models\Traits\WithSearch;
use App\Flare\Models\User;
use Database\Factories\CharacterFactory;

class Character extends Model
{

    use HasFactory, WithSearch;

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
        'can_adventure',
        'is_dead',
        'can_move_again_at',
        'can_attack_again_at',
        'can_craft_again_at',
        'can_adventure_again_at',
        'force_name_change',
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
        'inventory_max'          => 'integer',
        'can_attack'             => 'boolean',
        'can_move'               => 'boolean',
        'can_craft'              => 'boolean',
        'is_dead'                => 'boolean',
        'force_name_change'      => 'boolean',
        'can_move_again_at'      => 'datetime',
        'can_attack_again_at'    => 'datetime',
        'can_craft_again_at'     => 'datetime',
        'can_adventure_again_at' => 'datetime',
        'level'                  => 'integer',
        'xp'                     => 'float',
        'xp_next'                => 'integer',
        'str'                    => 'integer',
        'dur'                    => 'integer',
        'dex'                    => 'integer',
        'chr'                    => 'integer',
        'int'                    => 'integer',
        'ac'                     => 'integer',
        'gold'                   => 'integer',
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
        return $this->hasMany(AdventureLog::class);
    }

    public function notifications() {
        return $this->hasMany(Notification::class, 'character_id', 'id');
    }

    public function snapShots() {
        return $this->hasMany(CharacterSnapShot::class, 'character_id', 'id');
    }

    public function kingdoms() {
        return $this->hasMany(Kingdom::class, 'character_id', 'id');
    }

    public function getXpAttribute($value) {
        return number_format($value, 2);
    }

    /**
     * Allows one to get specific information from a character.
     *
     * By returning the CharacterInformationBuilder class, we can allow you to get 
     * multiple calulculated sets of data.
     *  
     * @return CharacterInformationBuilder
     */
    public function getInformation(): CharacterInformationBuilder {
        $info = resolve(CharacterInformationBuilder::class);

        return $info->setCharacter($this);
    }

    protected static function newFactory() {
        return CharacterFactory::new();
    }
}
