<?php

namespace App\Flare\Models;

use Database\Factories\CharacterGameLocationGemProgressionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterGameLocationGemProgression extends Model
{
    use HasFactory;

    protected $fillable = [
        'character_id',
        'game_location_gem_paramter_id',
        'level',
        'xp',
    ];

    protected $casts = [
        'character_id' => 'integer',
        'game_location_gem_paramter_id' => 'integer',
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
     * Get the Location Gem profile this personal progression belongs to.
     */
    public function gameLocationGemParamter(): BelongsTo
    {
        return $this->belongsTo(GameLocationGemParamter::class);
    }

    /**
     * Get the factory instance for this model.
     */
    protected static function newFactory(): CharacterGameLocationGemProgressionFactory
    {
        return CharacterGameLocationGemProgressionFactory::new();
    }
}
