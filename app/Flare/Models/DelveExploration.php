<?php

namespace App\Flare\Models;

use Database\Factories\DelveExplorationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DelveExploration extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'monster_id',
        'started_at',
        'completed_at',
        'ended_reason',
        'panel_dismissed_at',
        'attack_type',
        'increase_enemy_strength',
        'pack_size',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'panel_dismissed_at' => 'datetime',
        'increase_enemy_strength' => 'float',
        'pack_size' => 'integer',
    ];

    /**
     * The Character running this Delve.
     *
     * @return BelongsTo
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    /**
     * The Monster currently selected for this Delve.
     *
     * @return BelongsTo
     */
    public function monster(): BelongsTo
    {
        return $this->belongsTo(Monster::class);
    }

    /**
     * The persisted round logs for this Delve.
     *
     * @return HasMany
     */
    public function delveLogs(): HasMany
    {
        return $this->hasMany(DelveLog::class);
    }

    /**
     * Create the model factory for Delve explorations.
     *
     * @return DelveExplorationFactory
     */
    protected static function newFactory(): DelveExplorationFactory
    {
        return DelveExplorationFactory::new();
    }
}
