<?php

namespace App\Flare\Models;

use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Database\Factories\PassiveSkillFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Flare\Models\Traits\WithSearch;

class PassiveSkill extends Model
{

    use HasFactory, WithSearch;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'max_level',
        'hours_per_level',
        'bonus_per_level',
        'effect_type',
        'parent_skill_id',
        'unlocks_at_level',
        'is_locked',
        'is_parent',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'max_level'        => 'integer',
        'bonus_per_level'  => 'float',
        'effect_type'      => 'integer',
        'hours_per_level'  => 'integer',
        'item_find_chance' => 'float',
        'unlocks_at_level' => 'integer',
        'is_locked'        => 'boolean',
        'is_parent'        => 'boolean',
    ];

    

    public function passiveType(): PassiveSkillTypeValue {
        return new PassiveSkillTypeValue($this->effect_type);
    }

    public function childSkills() {
        return $this->hasMany($this, 'parent_skill_id')->with('childSkills');
    }

    public function parent() {
        return $this->belongsTo($this, 'parent_skill_id');
    }

    protected static function newFactory() {
        return PassiveSkillFactory::new();
    }
}
