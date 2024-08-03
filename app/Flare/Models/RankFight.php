<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class RankFight extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'current_rank',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'current_rank' => 'integer',
    ];
}
