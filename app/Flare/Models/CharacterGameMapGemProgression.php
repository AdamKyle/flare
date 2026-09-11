<?php

namespace App\Flare\Models;

use Database\Factories\CharacterGameMapGemProgressionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterGameMapGemProgression extends Model
{
    use HasFactory;

    protected $fillable = [
        'character_id',
        'game_map_gem_paramter_id',
        'level',
        'xp',
    ];

    protected $casts = [
        'character_id' => 'integer',
        'game_map_gem_paramter_id' => 'integer',
        'level' => 'integer',
        'xp' => 'integer',
    ];

    /**
     * Get the Character this personal progression belongs to.
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    /**
     * Get the Map Gem profile this personal progression belongs to.
     */
    public function gameMapGemParamter(): BelongsTo
    {
        return $this->belongsTo(GameMapGemParamter::class);
    }

    /**
     * Get the factory instance for this model.
     */
    protected static function newFactory(): CharacterGameMapGemProgressionFactory
    {
        return CharacterGameMapGemProgressionFactory::new();
    }
}
