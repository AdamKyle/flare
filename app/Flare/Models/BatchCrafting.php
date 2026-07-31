<?php

namespace App\Flare\Models;

use Database\Factories\BatchCraftingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatchCrafting extends Model
{
    use HasFactory;

    protected $fillable = [
        'character_id',
        'user_id',
        'batch_type',
        'disposition',
        'started_at',
        'ends_at',
        'completed_at',
        'cancelled_at',
        'ended_reason',
        'status',
        'progress',
        'info_acknowledged',
        'panel_dismissed_at',
        'selected_items',
        'selected_oils',
        'crafted_count',
        'sold_count',
        'destroyed_count',
        'listed_count',
        'kept_count',
        'applied_count',
        'skipped_count',
        'failed_count',
        'action_log',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ends_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'panel_dismissed_at' => 'datetime',
        'selected_items' => 'array',
        'selected_oils' => 'array',
        'progress' => 'array',
        'info_acknowledged' => 'boolean',
        'crafted_count' => 'integer',
        'sold_count' => 'integer',
        'destroyed_count' => 'integer',
        'listed_count' => 'integer',
        'kept_count' => 'integer',
        'applied_count' => 'integer',
        'skipped_count' => 'integer',
        'failed_count' => 'integer',
        'action_log' => 'array',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isRunning(): bool
    {
        return is_null($this->completed_at) && is_null($this->cancelled_at);
    }

    protected static function newFactory()
    {
        return BatchCraftingFactory::new();
    }
}
