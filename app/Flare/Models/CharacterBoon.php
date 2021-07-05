<?php

namespace App\Flare\Models;

use App\Game\Skills\Values\SkillTypeValue;
use Database\Factories\CharacterBoonFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CharacterBoon extends Model
{

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'type',
        'stat_bonus',
        'affect_skill_type',
        'affected_skill_bonus',
        'affected_skill_training_bonus',
        'affected_skill_base_damage_mod_bonus',
        'affected_skill_base_healing_mod_bonus',
        'affected_skill_base_ac_mod_bonus',
        'affected_skill_fight_time_out_mod_bonus',
        'affected_skill_move_time_out_mod_bonus',
        'started',
        'complete',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'character_id'                            => 'integer',
        'type'                                    => 'integer',
        'stat_bonus'                              => 'float',
        'affect_skill_type'                       => 'integer',
        'affected_skill_bonus'                    => 'float',
        'affected_skill_training_bonus'           => 'float',
        'affected_skill_base_damage_mod_bonus'    => 'float',
        'affected_skill_base_healing_mod_bonus'   => 'float',
        'affected_skill_base_ac_mod_bonus'        => 'float',
        'affected_skill_fight_time_out_mod_bonus' => 'float',
        'affected_skill_move_time_out_mod_bonus'  => 'float',
        'started'                                 => 'datetime',
        'complete'                                => 'datetime',
    ];

    public function character() {
        return $this->belongsTo(Character::class, 'character_id', 'id');
    }

    public function skillType(): SkillTypeValue {
        return new SkillTypeValue($this->affcted_skill_type);
    }

    protected static function newFactory() {
        return CharacterBoonFactory::new();
    }
}
