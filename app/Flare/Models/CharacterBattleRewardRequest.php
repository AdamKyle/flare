<?php

namespace App\Flare\Models;

use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestPriority;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestStatus;
use Database\Factories\CharacterBattleRewardRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CharacterBattleRewardRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'character_id',
        'priority',
        'source_type',
        'source_id',
        'handler_payload',
        'status',
        'failed_reason',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'priority' => BattleRewardRequestPriority::class,
        'source_type' => BattleRewardRequestSourceType::class,
        'handler_payload' => 'array',
        'status' => BattleRewardRequestStatus::class,
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(CharacterBattleRewardRequestStep::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CharacterBattleRewardRequestMessage::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', BattleRewardRequestStatus::PENDING);
    }

    public function scopeProcessing(Builder $query): Builder
    {
        return $query->where('status', BattleRewardRequestStatus::PROCESSING);
    }

    public function scopeResumable(Builder $query): Builder
    {
        return $query->where('status', BattleRewardRequestStatus::RESUMABLE);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', BattleRewardRequestStatus::COMPLETED);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', BattleRewardRequestStatus::FAILED);
    }

    public function scopeQueued(Builder $query): Builder
    {
        return $query->whereIn('status', [
            BattleRewardRequestStatus::PENDING,
            BattleRewardRequestStatus::PROCESSING,
            BattleRewardRequestStatus::RESUMABLE,
        ]);
    }

    public function scopeForCharacter(Builder $query, int $characterId): Builder
    {
        return $query->where('character_id', $characterId);
    }

    public function scopeOrderedForProcessing(Builder $query): Builder
    {
        return $query
            ->orderByRaw(
                'CASE priority WHEN ? THEN 1 WHEN ? THEN 2 WHEN ? THEN 3 ELSE 4 END',
                [
                    BattleRewardRequestPriority::FIRST->value,
                    BattleRewardRequestPriority::SECOND->value,
                    BattleRewardRequestPriority::THIRD->value,
                ],
            )
            ->orderBy('id');
    }

    protected static function newFactory(): CharacterBattleRewardRequestFactory
    {
        return CharacterBattleRewardRequestFactory::new();
    }
}
