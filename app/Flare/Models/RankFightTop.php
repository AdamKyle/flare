<?php

namespace App\Flare\Models;

use App\Flare\Values\CharacterClassValue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\GameClassFactory;
use App\Flare\Models\Traits\WithSearch;

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
