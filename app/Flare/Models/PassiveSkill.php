<?php

namespace App\Flare\Models;

use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Database\Factories\PassiveSkillFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PassiveSkill extends Model
{
    use HasFactory;

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
        'resource_bonus_per_level',
        'effect_type',
        'parent_skill_id',
        'unlocks_at_level',
        'is_locked',
        'is_parent',
        'unlocks_game_building_id',
        'capital_city_building_request_travel_time_reduction',
        'capital_city_unit_request_travel_time_reduction',
        'resource_request_time_reduction',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'max_level' => 'integer',
        'bonus_per_level' => 'float',
        'effect_type' => 'integer',
        'hours_per_level' => 'integer',
        'item_find_chance' => 'float',
        'unlocks_at_level' => 'integer',
        'is_locked' => 'boolean',
        'is_parent' => 'boolean',
        'unlocks_game_building_id' => 'integer',
        'resource_bonus_per_level' => 'integer',
        'capital_city_building_request_travel_time_reduction' => 'float',
        'capital_city_unit_request_travel_time_reduction' => 'float',
        'resource_request_time_reduction' => 'float',
    ];

    public function passiveType(): PassiveSkillTypeValue
    {
        return new PassiveSkillTypeValue($this->effect_type);
    }

    public function childSkills()
    {
        return $this->hasMany($this, 'parent_skill_id')->with('childSkills');
    }

    public function parent()
    {
        return $this->belongsTo($this, 'parent_skill_id');
    }

    public function gameBuilding()
    {
        return $this->hasOne(GameBuilding::class, 'unlocks_game_building_id', 'id');
    }

    protected static function newFactory()
    {
        return PassiveSkillFactory::new();
    }
}
