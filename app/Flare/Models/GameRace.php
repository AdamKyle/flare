<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\GameRaceFactory;
use App\Flare\Models\Traits\WithSearch;

class GameRace extends Model
{

    use HasFactory, WithSearch;

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
        'agi_mod',
        'focus_mod',
        'accuracy_mod',
        'dodge_mod',
        'defense_mod',
        'looting_mod',
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
        'agi_mod'      => 'integer',
        'focus_mod'    => 'integer',
        'accuracy_mod' => 'float',
        'dodge_mod'    => 'float',
        'defense_mod'  => 'float',
        'looting_mod'  => 'float',
    ];

    protected static function newFactory() {
        return GameRaceFactory::new();
    }

}
