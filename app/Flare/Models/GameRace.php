<?php

namespace App\Flare\Models;

use Database\Factories\GameRaceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameRace extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'image_path',
    ];

    /**
     * Resolve the factory used to create new Race instances.
     *
     * @return GameRaceFactory
     */
    protected static function newFactory()
    {
        return GameRaceFactory::new();
    }
}
