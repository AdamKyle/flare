<?php

namespace App\Flare\Models;

use Database\Factories\GameLocationGemProgressionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameLocationGemProgression extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_location_gem_paramter_id',
        'level',
        'xp',
    ];

    protected $casts = [
        'game_location_gem_paramter_id' => 'integer',
        'level' => 'integer',
        'xp' => 'integer',
    ];

    /**
     * Get the Location Gem profile this shared global progression belongs to.
     */
    public function gameLocationGemParamter(): BelongsTo
    {
        return $this->belongsTo(GameLocationGemParamter::class);
    }

    /**
     * Get the factory instance for this model.
     */
    protected static function newFactory(): GameLocationGemProgressionFactory
    {
        return GameLocationGemProgressionFactory::new();
    }
}
