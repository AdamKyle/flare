<?php

namespace App\Flare\Models;

use Database\Factories\CharacterGameLocationGemScrollFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterGameLocationGemScroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'character_id',
        'game_location_gem_paramter_id',
        'item_id',
        'started_at',
        'expires_at',
    ];

    protected $casts = [
        'character_id' => 'integer',
        'game_location_gem_paramter_id' => 'integer',
        'item_id' => 'integer',
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the Character this active Gem Scroll belongs to.
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    /**
     * Get the Location Gem profile this active Gem Scroll is applied to.
     */
    public function gameLocationGemParamter(): BelongsTo
    {
        return $this->belongsTo(GameLocationGemParamter::class);
    }

    /**
     * Get the generated Gem Scroll Item this active row was created from.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Scope the query to only unexpired active Gem Scroll rows.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Get the factory instance for this model.
     */
    protected static function newFactory(): CharacterGameLocationGemScrollFactory
    {
        return CharacterGameLocationGemScrollFactory::new();
    }
}
