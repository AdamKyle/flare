<?php

namespace App\Flare\Models;

use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use Database\Factories\CharacterBattleRewardRequestMessageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterBattleRewardRequestMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'character_battle_reward_request_id',
        'character_id',
        'user_id',
        'step_name',
        'message',
        'message_id',
        'source',
        'item_id',
        'link_text',
        'emitted_at',
    ];

    protected $casts = [
        'step_name' => BattleRewardStepName::class,
        'message_id' => 'integer',
        'item_id' => 'integer',
        'emitted_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(CharacterBattleRewardRequest::class, 'character_battle_reward_request_id');
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeEmitted(Builder $query): Builder
    {
        return $query->whereNotNull('emitted_at');
    }

    public function scopeUnemitted(Builder $query): Builder
    {
        return $query->whereNull('emitted_at');
    }

    protected static function newFactory(): CharacterBattleRewardRequestMessageFactory
    {
        return CharacterBattleRewardRequestMessageFactory::new();
    }
}
