<?php

namespace App\Flare\Models;

use App\Flare\Values\CharacterClassValue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\GameClassFactory;
use App\Flare\Models\Traits\WithSearch;

class RankFight extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'current_rank'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'current_rank' => 'integer'
    ];
}
