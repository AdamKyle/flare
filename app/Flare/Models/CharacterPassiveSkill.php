<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterPassiveSkill extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'passive_skill_id',
        'parent_skill_id',
        'current_level',
        'hours_to_next',
        'started_at',
        'completed_at',
        'is_locked',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'current_level' => 'integer',
        'hours_to_next' => 'integer',
        'passive_skill_id' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'is_locked' => 'boolean',
    ];

    protected $appends = [
        'name',
        'is_max_level',
        'current_bonus',
        'resource_request_time_reduction',
        'resource_increase_amount',
        'capital_city_building_request_travel_time_reduction',
        'capital_city_unit_request_travel_time_reduction',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }

    public function passiveSkill()
    {
        return $this->belongsTo(PassiveSkill::class, 'passive_skill_id', 'id');
    }

    public function children()
    {
        return $this->hasMany($this, 'parent_skill_id')->with('children');
    }

    public function getIsMaxLevelAttribute()
    {
        return $this->current_level === $this->passiveSkill->max_level;
    }

    public function getCurrentBonusAttribute()
    {
        return $this->current_level * $this->passiveSkill->bonus_per_level;
    }

    public function getResourceIncreaseAmountAttribute()
    {
        return $this->current_level * $this->passiveSkill->resource_bonus_per_level;
    }

    public function getNameAttribute()
    {
        return $this->passiveSkill->name;
    }

    public function getResourceRequestTimeReductionAttribute()
    {
        return $this->current_level * $this->passiveSkill->resource_request_time_reduction;
    }

    public function getCapitalCityBuildingRequestTravelTimeReductionAttribute()
    {
        return $this->current_level * $this->passiveSkill->capital_city_building_request_travel_time_reduction;
    }

    public function getCapitalCityUnitRequestTravelTimeReductionAttribute()
    {
        return $this->level * $this->passiveSkill->capital_city_unit_request_travel_time_reduction;
    }
}
