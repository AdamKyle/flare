<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Flare\Models\Traits\WithSearch;
use Database\Factories\GameBuildingFactory;

class CharacterClassSpecialtiesEquipped extends Model
{

    protected $table = 'character_class_specialties_equipped';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'game_class_special_id',
        'level',
        'current_xp',
        'required_xp',
        'equipped'
    ];

    protected $casts = [
        'level'       => 'integer',
        'current_xp'  => 'integer',
        'required_xp' => 'integer',
        'equipped'    => 'boolean',
    ];

    protected $appends = [
        'specialty_damage',
        'increase_specialty_damage_per_level',
        'base_damage_mod',
        'base_ac_mod',
        'base_healing_mod',
        'base_spell_damage_mod',
        'health_mod',
        'base_damage_stat_increase',
    ];

    public function getSpecialtyDamageAttribute() {
        $baseDamage          = $this->gameClassSpecial->specialty_damage;
        $addedDamage         = $this->gameClassSpecial->increase_specialty_damage_per_level * $this->level;
        $characterDamageStat =  $this->character->getInformation()->statMod($this->character->damage_stat) * $this->gameClassSpecial->specialty_damage_uses_damage_stat_amount;

        return $baseDamage + $addedDamage + $characterDamageStat;
    }

    public function getBaseDamageModAttribute() {
        return $this->gameClassSpecial->base_damage_mod * $this->level;
    }

    public function getBaseAcModAttribute() {
        return $this->gameClassSpecial->base_ac_mod * $this->level;
    }

    public function getBaseHealingModAttribute() {
        return $this->gameClassSpecial->base_healing_mod * $this->level;
    }

    public function getBaseSpellDamageMod() {
        return $this->gameClassSpecial->base_spell_damage_mod * $this->level;
    }

    public function getHealthModAttribute() {
        return $this->gameClassSpecial->health_mod * $this->level;
    }

    public function getBaseDamageStatIncreaseAttribute() {
        return $this->gameClassSpecial->base_damage_stat_increase * $this->level;
    }

    public function getIncreasesSpecialtyDamagePerLevelAttribute() {
        return $this->gameClassSpecial->increase_specialty_damage_per_level * $this->level;
    }

    public function gameClassSpecial() {
        return $this->hasOne(GameClassSpecial::class, 'game_class_special_id', 'id');
    }

    public function character() {
        return $this->hasOne(Character::class, 'character_id', 'id');
    }
}
