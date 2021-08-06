<?php

namespace App\Flare\Models;

use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\GameSkillFactory;
use App\Flare\Models\Traits\WithSearch;

class GameSkill extends Model
{

    use HasFactory, WithSearch;

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
        'can_monsters_have_skill',
        'required_equipment_type',
        'can_train',
        'skill_bonus_per_level',
        'game_class_id',
        'is_locked'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'base_damage_mod_bonus_per_level'    => 'float',
        'base_healing_mod_bonus_per_level'   => 'float',
        'base_ac_mod_bonus_per_level'        => 'float',
        'fight_time_out_mod_bonus_per_level' => 'float',
        'move_time_out_mod_bonus_per_level'  => 'float',
        'skill_bonus_per_level'              => 'float',
        'can_monsters_have_skill'            => 'boolean',
        'can_train'                          => 'boolean',
        'is_locked'                          => 'integer',
        'type'                               => 'integer',
    ];

    public function skillType(): SkillTypeValue {
        return new SkillTypeValue($this->type);
    }

    public function gameClass() {
        return $this->belongsTo(GameClass::class, 'game_class_id', 'id');
    }

    protected static function newFactory() {
        return GameSkillFactory::new();
    }
}
