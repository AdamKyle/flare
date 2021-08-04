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
        'skill_bonus',
        'base_damage_mod_bonus',
        'base_healing_mod_bonus',
        'base_ac_mod_bonus',
        'fight_time_out_mod_bonus',
        'move_time_out_mod_bonus',
        'skill_training_bonus',
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
        'skill_bonus'                             => 'float',
        'base_damage_mod_bonus'                   => 'float',
        'base_healing_mod_bonus'                  => 'float',
        'base_ac_mod_bonus'                       => 'float',
        'fight_time_out_mod_bonus'                => 'float',
        'move_time_out_mod_bonus'                 => 'float',
        'skill_training_bonus'                    => 'float',
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
