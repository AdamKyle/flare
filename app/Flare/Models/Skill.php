<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\SkillFactory;
use Illuminate\Support\Str;

class Skill extends Model
{

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'monster_id',
        'game_skill_id',
        'currently_training',
        'level',
        'xp',
        'xp_max',
        'xp_towards',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'currently_training'    => 'boolean',
        'level'                 => 'integer',
        'xp'                    => 'integer',
        'xp_max'                => 'integer',
        'xp_towards'            => 'float',
    ];

    public function baseSkill() {
        return $this->belongsTo(GameSkill::class, 'game_skill_id', 'id');
    }

    public function character() {
        return $this->belongsTo(Character::class);
    }

    public function getNameAttribute() {
        return $this->baseSkill->name;
    }

    public function getDescriptionAttribute() {
        return $this->baseSkill->description;
    }

    public function getMaxLevelAttribute() {
        return $this->baseSkill->max_level;
    }

    public function getCanTrainAttribute() {
        return $this->baseSkill->can_train;
    }

    public function getBaseDamageModAttribute() {
        return ($this->baseSkill->base_damage_mod_bonus_per_level * $this->level) - $this->baseSkill->base_damage_mod_bonus_per_level;
    }

    public function getBaseHealingModAttribute() {
        return ($this->baseSkill->base_healing_mod_bonus_per_level * $this->level) - $this->baseSkill->base_healing_mod_bonus_per_level;
    }

    public function getBaseACModAttribute() {
        return ($this->baseSkill->base_ac_mod_bonus_per_level * $this->level) - $this->baseSkill->base_ac_mod_bonus_per_level;
    }

    public function getFightTimeOutModAttribute() {
        return ($this->baseSkill->fight_time_out_mod_bonus_per_level * $this->level) - $this->baseSkill->fight_time_out_mod_bonus_per_level;
    }

    public function getMoveTimeOutModAttribute() {
        return ($this->baseSkill->move_time_out_mod_bonus_per_level * $this->level) - $this->baseSkill->move_time_out_mod_bonus_per_level;
    }

    public function getSkillBonusAttribute() {
        if (is_null($this->character)) {
            // Monsters base skill:
            return ($this->baseSkill->skill_bonus_per_level * $this->level);
        }
        
        switch ($this->baseSkill->name) {
            case 'Accuracy':
                return $this->getCharacterSkillBonus($this->character, 'Accuracy') + (($this->baseSkill->skill_bonus_per_level * $this->level) - $this->baseSkill->skill_bonus_per_level);
            case 'Looting':
                return $this->getCharacterSkillBonus($this->character, 'Looting') + (($this->baseSkill->skill_bonus_per_level * $this->level) - $this->baseSkill->skill_bonus_per_level);
            case 'Dodge':
                return $this->getCharacterSkillBonus($this->character, 'Dodge') + (($this->baseSkill->skill_bonus_per_level * $this->level) - $this->baseSkill->skill_bonus_per_level);
            default:
            return ($this->baseSkill->skill_bonus_per_level * $this->level) - $this->baseSkill->skill_bonus_per_level;
        }
    }

    protected function getCharacterSkillBonus(Character $character, string $name): int {
        $raceSkillBonusValue  = $character->race->{Str::snake($name . '_mod')};
        $classSkillBonusValue = $character->class->{Str::snake($name . '_mod')};
  
        if (!is_null($raceSkillBonusValue) && !is_null($classSkillBonusValue)) {
            return round($raceSkillBonusValue + $classSkillBonusValue);
        }
  
        return 0;
    }

    protected static function newFactory() {
        return SkillFactory::new();
    }
}
