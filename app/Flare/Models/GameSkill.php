<?php

namespace App\Flare\Models;

use App\Game\Skills\Values\SkillTypeValue;
use Database\Factories\GameSkillFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameSkill extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'description',
        'name',
        'max_level',
        'type',
        'game_class_id',
        'base_damage_mod_bonus_per_level',
        'base_healing_mod_bonus_per_level',
        'base_ac_mod_bonus_per_level',
        'fight_time_out_mod_bonus_per_level',
        'move_time_out_mod_bonus_per_level',
        'unit_time_reduction',
        'building_time_reduction',
        'unit_movement_time_reduction',
        'can_train',
        'skill_bonus_per_level',
        'is_locked',
        'class_bonus',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'base_damage_mod_bonus_per_level' => 'float',
        'base_healing_mod_bonus_per_level' => 'float',
        'base_ac_mod_bonus_per_level' => 'float',
        'fight_time_out_mod_bonus_per_level' => 'float',
        'move_time_out_mod_bonus_per_level' => 'float',
        'skill_bonus_per_level' => 'float',
        'unit_time_reduction' => 'float',
        'building_time_reduction' => 'float',
        'unit_movement_time_reduction' => 'float',
        'class_bonus' => 'float',
        'can_train' => 'boolean',
        'is_locked' => 'integer',
        'type' => 'integer',
    ];

    public function skillType(): SkillTypeValue
    {
        return SkillTypeValue::tryFrom($this->type);
    }

    public function gameClass()
    {
        return $this->belongsTo(GameClass::class, 'game_class_id', 'id');
    }

    protected static function newFactory()
    {
        return GameSkillFactory::new();
    }
}
