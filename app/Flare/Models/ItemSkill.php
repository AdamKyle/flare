<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class ItemSkill extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'str_mod',
        'dex_mod',
        'dur_mod',
        'chr_mod',
        'focus_mod',
        'int_mod',
        'agi_mod',
        'base_damage_mod',
        'base_ac_mod',
        'base_healing_mod',
        'max_level',
        'total_kills_needed',
        'parent_id',
        'parent_level_needed',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'str_mod'             => 'float',
        'dex_mod'             => 'float',
        'dur_mod'             => 'float',
        'chr_mod'             => 'float',
        'focus_mod'           => 'float',
        'int_mod'             => 'float',
        'agi_mod'             => 'float',
        'base_damage_mod'     => 'float',
        'base_ac_mod'         => 'float',
        'base_healing_mod'    => 'float',
        'max_level'           => 'integer',
        'total_kills_needed'  => 'integer',
        'parent_id'           => 'integer',
        'parent_level_needed' => 'integer'
    ];

    public function parent() {
        return $this->belongsTo($this, 'parent_id');
    }

    public function children() {
        return $this->hasMany($this, 'parent_id')
                    ->with(
                        'children'
                    );
    }
}
