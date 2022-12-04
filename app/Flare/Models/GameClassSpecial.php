<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Flare\Models\Traits\WithSearch;
use Database\Factories\GameBuildingFactory;

class GameClassSpecial extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'game_class_id',
        'name',
        'description',
        'requires_class_rank_level',
        'specialty_damage',
        'increase_specialty_damage_per_level',
        'specialty_damage_uses_damage_stat_amount',
        'base_damage_mod',
        'base_ac_mod',
        'base_healing_mod',
        'base_spell_damage_mod',
        'health_mod',
        'base_damage_stat_increase',
    ];

    protected $casts = [
        'game_class_id'                             => 'integer',
        'requires_class_rank_level'                 => 'integer',
        'specialty_damage'                          => 'integer',
        'increase_specialty_damage_per_level'       => 'integer',
        'specialty_damage_uses_damage_stat_amount'  => 'float',
        'base_damage_mod'                           => 'float',
        'base_ac_mod'                               => 'float',
        'base_healing_mod'                          => 'float',
        'base_spell_damage_mod'                     => 'float',
        'health_mod'                                => 'float',
        'base_damage_stat_increase'                 => 'float',
    ];

    public function gameClass() {
        return $this->hasOne(GameClass::class, 'id', 'game_class_id');
    }

}
