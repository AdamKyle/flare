<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\SkillFactory;

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
        'base_damage_mod',
        'base_healing_mod',
        'base_ac_mod',
        'fight_time_out_mod',
        'move_time_out_mod',
        'skill_bonus',
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
        'base_damage_mod'       => 'float',
        'base_healing_mod'      => 'float',
        'base_ac_mod'           => 'float',
        'fight_time_out_mod'    => 'float',
        'move_time_out_mod'     => 'float',
        'skill_bonus'           => 'float',
    ];

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

    public function baseSkill() {
        return $this->belongsTo(GameSkill::class, 'game_skill_id', 'id');
    }

    public function character() {
        return $this->belongsTo(Character::class);
    }

    protected static function newFactory() {
        return SkillFactory::new();
    }
}
