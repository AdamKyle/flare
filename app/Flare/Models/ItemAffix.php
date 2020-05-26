<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use App\Flare\Models\Item;

class ItemAffix extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'base_damage_mod',
        'base_ac_mod',
        'type',
        'description',
        'base_healing_mod',
        'str_mod',
        'dur_mod',
        'dex_mod',
        'chr_mod',
        'int_mod',
        'ac_mod',
        'skill_name',
        'skill_training_bonus',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'base_damage_mod'      => 'float',
        'base_healing_mod'     => 'float',
        'str_mod'              => 'float',
        'dur_mod'              => 'float',
        'dex_mod'              => 'float',
        'chr_mod'              => 'float',
        'int_mod'              => 'float',
        'ac_mod'               => 'float',
        'skill_training_bonus' => 'float',
    ];
}
