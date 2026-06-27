<?php

namespace App\Flare\Models;

use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepStatus;
use Database\Factories\CharacterBattleRewardRequestStepFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterBattleRewardRequestStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'character_battle_reward_request_id',
        'character_id',
        'step_name',
        'status',
        'payload_json',
        'result_json',
        'checkpoint_json',
        'started_at',
        'heartbeat_at',
        'completed_at',
        'failed_at',
        'failed_reason',
        'attempts',
    ];

    protected $casts = [
        'step_name' => BattleRewardStepName::class,
        'status' => BattleRewardStepStatus::class,
        'payload_json' => 'array',
        'result_json' => 'array',
        'checkpoint_json' => 'array',
        'started_at' => 'datetime',
        'heartbeat_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(CharacterBattleRewardRequest::class, 'character_battle_reward_request_id');
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', BattleRewardStepStatus::PENDING);
    }

    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('status', BattleRewardStepStatus::RUNNING);
    }

    public function scopeCheckpointed(Builder $query): Builder
    {
        return $query->where('status', BattleRewardStepStatus::CHECKPOINTED);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', BattleRewardStepStatus::COMPLETED);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', BattleRewardStepStatus::FAILED);
    }

    public function scopeResumable(Builder $query): Builder
    {
        return $query->where('status', BattleRewardStepStatus::RESUMABLE);
    }

    protected static function newFactory(): CharacterBattleRewardRequestStepFactory
    {
        return CharacterBattleRewardRequestStepFactory::new();
    }
}
