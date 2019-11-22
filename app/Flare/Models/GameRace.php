<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class GameRace extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'str_mod',
        'dur_mod',
        'dex_mod',
        'chr_mod',
        'int_mod',
        'accuracy_mod',
        'dodge_mod',
        'deffense_mod',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'str_mod'      => 'integer',
        'dur_mod'      => 'integer',
        'dex_mod'      => 'integer',
        'chr_mod'      => 'integer',
        'int_mod'      => 'integer',
        'accuracy_mod' => 'integer',
        'dodge_mod'    => 'integer',
        'deffense_mod' => 'integer',
    ];
}
