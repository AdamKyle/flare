<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class Monster extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'damage_stat',
        'xp',
        'str',
        'dur',
        'dex',
        'chr',
        'int',
        'ac',
        'health_range',
        'attack_range',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'xp'       => 'integer',
        'str'      => 'integer',
        'dur'      => 'integer',
        'dex'      => 'integer',
        'chr'      => 'integer',
        'int'      => 'integer',
        'ac'       => 'integer',
    ];
}
