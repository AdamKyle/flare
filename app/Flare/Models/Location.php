<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'asset_path',
        'is_port',
        'x',
        'y',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'y'       => 'integer',
        'x'       => 'integer',
        'is_port' => 'boolean',
    ];
}
