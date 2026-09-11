<?php

namespace App\Flare\Models;

use Database\Factories\GameMapGemProgressionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameMapGemProgression extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_map_gem_paramter_id',
        'level',
        'xp',
    ];

    protected $casts = [
        'game_map_gem_paramter_id' => 'integer',
        'level' => 'integer',
        'xp' => 'integer',
    ];

    /**
     * Get the Map Gem profile this shared global progression belongs to.
     */
    public function gameMapGemParamter(): BelongsTo
    {
        return $this->belongsTo(GameMapGemParamter::class);
    }

    /**
     * Get the factory instance for this model.
     */
    protected static function newFactory(): GameMapGemProgressionFactory
    {
        return GameMapGemProgressionFactory::new();
    }
}
