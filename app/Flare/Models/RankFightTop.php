<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class RankFightTop extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'current_rank',
        'rank_achievement_date',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'character_id'          => 'integer',
        'current_rank'          => 'integer',
        'rank_achievement_date' => 'datetime'
    ];

    public function character() {
        return $this->belongsTo(Character::class, 'character_id', 'id');
    }
}
