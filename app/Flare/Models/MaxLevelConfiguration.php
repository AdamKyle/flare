<?php

namespace App\Flare\Models;

use Database\Factories\MaxLevelConfigurationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaxLevelConfiguration extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'max_level',
        'half_way',
        'three_quarters',
        'last_leg',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'half_way' => 'integer',
        'three_quarters' => 'integer',
        'last_leg' => 'integer',
    ];

    protected static function newFactory()
    {
        return MaxLevelConfigurationFactory::new();
    }
}
