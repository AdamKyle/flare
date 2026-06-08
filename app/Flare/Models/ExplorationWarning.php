<?php

namespace App\Flare\Models;

use Database\Factories\ExplorationWarningFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExplorationWarning extends Model
{
    use HasFactory;

    protected $fillable = [
        'character_id',
        'user_id',
        'exploration_log_id',
        'type',
        'message',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function explorationLog(): BelongsTo
    {
        return $this->belongsTo(ExplorationLog::class);
    }

    protected static function newFactory(): ExplorationWarningFactory
    {
        return ExplorationWarningFactory::new();
    }
}
