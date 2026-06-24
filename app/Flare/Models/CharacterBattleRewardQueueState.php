<?php

namespace App\Flare\Models;

use Database\Factories\CharacterBattleRewardQueueStateFactory;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterBattleRewardQueueState extends Model
{
    use HasFactory;

    protected $fillable = [
        'character_id',
        'is_processing',
        'started_at',
        'heartbeat_at',
    ];

    protected $casts = [
        'is_processing' => 'boolean',
        'started_at' => 'datetime',
        'heartbeat_at' => 'datetime',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function scopeStale(Builder $query, CarbonInterface $cutoff): Builder
    {
        return $query
            ->where('is_processing', true)
            ->where(function (Builder $staleQuery) use ($cutoff): void {
                $staleQuery->whereNull('heartbeat_at')
                    ->orWhere('heartbeat_at', '<=', $cutoff);
            });
    }

    protected static function newFactory(): CharacterBattleRewardQueueStateFactory
    {
        return CharacterBattleRewardQueueStateFactory::new();
    }
}
