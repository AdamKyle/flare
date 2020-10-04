<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\GameClassFactory;

class GameClass extends Model
{

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'damage_stat',
        'str_mod',
        'dur_mod',
        'dex_mod',
        'chr_mod',
        'int_mod',
        'accuracy_mod',
        'dodge_mod',
        'deffense_mod',
    ];

    public static function dataTableSearch($query) {
        return empty($query) ? static::query()
            : static::where('name', 'like', '%'.$query.'%');
    }

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

    protected static function newFactory() {
        return GameClassFactory::new();
    }
}
