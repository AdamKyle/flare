<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterClassRankWeaponMastery extends Model
{
    protected $table = 'character_class_ranks_weapon_masteries';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'class_rank_id',
        'weapon_type',
        'current_xp',
        'required_xp',
        'level',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'class_rank_id' => 'integer',
        'weapon_type' => 'string',
        'current_xp' => 'integer',
        'required_xp' => 'integer',
        'level' => 'integer',
    ];

    public function classRank()
    {
        return $this->belongsTo(CharacterClassRank::class, 'class_rank_id', 'id');
    }
}
