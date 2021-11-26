<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\AdventureFactory;
use App\Flare\Models\Traits\WithSearch;

class PassiveSkill extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'max_level',
        'bonus_per_level',
        'effect_type',
        'child_skill',
        'unlocks_at_level',
        'is_locked',
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
        'item_find_chance' => 'float',
        'unlocks_at_level' => 'integer',
        'is_locked'        => 'boolean',
    ];

    public function monsters() {
        return $this->hasMany($this, 'child_skill');
    }
}
