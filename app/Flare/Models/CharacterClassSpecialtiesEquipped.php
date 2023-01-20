<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

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
        'spell_evasion',
        'affix_damage_reduction',
        'healing_reduction',
        'skill_reduction',
        'resistance_reduction',
    ];

    public function getSpecialtyDamageAttribute() {
        $cache = Cache::get('character-attack-data-' . $this->character->id);

        if (is_null($cache)) {
           return 0;
        }

        if (!isset($cache['damage_stat_amount'])) {
           return 0;
        }

        $baseDamage          = $this->gameClassSpecial->specialty_damage;
        $addedDamage         = $this->gameClassSpecial->increase_specialty_damage_per_level * $this->level;
        $characterDamageStat = $cache['damage_stat_amount'] * $this->gameClassSpecial->specialty_damage_uses_damage_stat_amount;

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

    public function getBaseSpellDamageModAttribute() {
        return $this->gameClassSpecial->base_spell_damage_mod * $this->level;
    }

    public function getHealthModAttribute() {
        return $this->gameClassSpecial->health_mod * $this->level;
    }

    public function getBaseDamageStatIncreaseAttribute() {
        return $this->gameClassSpecial->base_damage_stat_increase * $this->level;
    }

    public function getIncreaseSpecialtyDamagePerLevelAttribute() {
        return $this->gameClassSpecial->increase_specialty_damage_per_level * $this->level;
    }

    public function getSpellEvasionAttribute() {
        return $this->gameClassSpecial->spell_evasion * $this->level;
    }

    public function getAffixDamageReductionAttribute() {
        return $this->gameClassSpecial->affix_damage_reduction * $this->level;
    }

    public function getHealingReductionAttribute() {
        return $this->gameClassSpecial->healing_reduction * $this->level;
    }

    public function getSkillReductionAttribute() {
        return $this->gameClassSpecial->skill_reduction * $this->level;
    }

    public function getResistanceReductionAttribute() {
        return $this->gameClassSpecial->resistance_reduction * $this->level;
    }

    public function gameClassSpecial() {
        return $this->belongsTo(GameClassSpecial::class, 'game_class_special_id', 'id');
    }

    public function character() {
        return $this->belongsTo(Character::class);
    }
}
