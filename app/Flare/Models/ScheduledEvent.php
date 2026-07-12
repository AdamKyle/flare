<?php

namespace App\Flare\Models;

use App\Game\Events\Values\EventType;
use App\Game\Events\Values\ScheduledEventStatus;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Database\Factories\ScheduledEventFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ScheduledEvent extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'event_type',
        'raid_id',
        'parent_scheduled_event_id',
        'start_date',
        'end_date',
        'description',
        'currently_running',
        'raids_for_event',
        'status',
        'cancelled_at',
    ];

    protected $casts = [
        'event_type' => 'integer',
        'raid_id' => 'integer',
        'parent_scheduled_event_id' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'currently_running' => 'boolean',
        'raids_for_event' => 'array',
        'status' => 'string',
        'cancelled_at' => 'datetime',
    ];

    public function raid()
    {
        return $this->hasOne(Raid::class, 'id', 'raid_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ScheduledEvent::class, 'parent_scheduled_event_id', 'id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ScheduledEvent::class, 'parent_scheduled_event_id', 'id');
    }

    public function runtimeEvent(): HasOne
    {
        return $this->hasOne(Event::class, 'scheduled_event_id', 'id');
    }

    public function status(): ScheduledEventStatus
    {
        return new ScheduledEventStatus($this->status);
    }

    /**
     * Updates status and the legacy currently_running boolean together, keeping
     * the boolean in sync with the state/boolean contract for the given status.
     */
    public function applyStatus(string $status, ?Carbon $cancelledAt = null): void
    {
        $this->update([
            'status' => $status,
            'currently_running' => (new ScheduledEventStatus($status))->currentlyRunningBoolean(),
            'cancelled_at' => $cancelledAt,
        ]);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ScheduledEventStatus::activeStatuses());
    }

    /**
     * Schedules genuinely running at the exact given moment: running status,
     * the legacy currently_running boolean set, inside the schedule's own
     * start/end window, and backed by an exact runtime event that has itself
     * started and has not ended. Callers must capture $now once per
     * operation rather than letting each row evaluate its own now().
     */
    public function scopeRunningNow(Builder $query, CarbonInterface $now): Builder
    {
        return $query->where('status', ScheduledEventStatus::RUNNING)
            ->where('currently_running', true)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>', $now)
            ->whereHas('runtimeEvent', function (Builder $runtimeQuery) use ($now): void {
                $runtimeQuery->where('started_at', '<=', $now)
                    ->where(function (Builder $endedQuery) use ($now): void {
                        $endedQuery->where('ends_at', '>', $now)
                            ->orWhereNull('ends_at');
                    });
            });
    }

    public function getTitleOfEvent(): string
    {
        if (! is_null($this->raid)) {
            return $this->raid->name;
        }

        $type = new EventType($this->event_type);

        if ($type->isWeeklyCelestials()) {
            return 'Weekly Celestials';
        }

        if ($type->isWeeklyCurrencyDrops()) {
            return 'Weekly Currency Drops';
        }

        if ($type->isWeeklyFactionLoyaltyEvent()) {
            return 'Weekly Faction Loyalty Event';
        }

        if ($type->isWinterEvent()) {
            return 'The Winter Event';
        }

        if ($type->isDelusionalMemoriesEvent()) {
            return 'Delusional Memories Event';
        }

        return 'Event Name';
    }

    protected static function newFactory()
    {
        return ScheduledEventFactory::new();
    }
}
