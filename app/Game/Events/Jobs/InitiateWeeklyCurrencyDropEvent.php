<?php

namespace App\Game\Events\Jobs;

use App\Flare\Models\Event;
use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\ScheduledEventStatus;
use App\Game\Messages\Events\GlobalMessageEvent;
use Facades\App\Game\Core\Handlers\AnnouncementHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class InitiateWeeklyCurrencyDropEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $eventId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $eventId)
    {
        $this->eventId = $eventId;
    }

    public function handle(): void
    {
        $scheduledEvent = ScheduledEvent::find($this->eventId);

        if (is_null($scheduledEvent) || ! $scheduledEvent->status()->isQueued()) {
            return;
        }

        $scheduledEvent->applyStatus(ScheduledEventStatus::STARTING);

        try {
            if (Event::where('scheduled_event_id', $scheduledEvent->id)->exists()) {
                $scheduledEvent->applyStatus(ScheduledEventStatus::RUNNING);

                return;
            }

            $createdEvent = Event::create([
                'type' => EventType::WEEKLY_CURRENCY_DROPS,
                'started_at' => $scheduledEvent->start_date,
                'ends_at' => $scheduledEvent->end_date,
                'scheduled_event_id' => $scheduledEvent->id,
            ]);

            event(new GlobalMessageEvent('Currencies are dropping like crazy! Shards, Copper Coins (for those with the quest item) and
        Gold Dust are falling off the enemies for one day only! At a rate of 1-50 per currency type.'));

            AnnouncementHandler::createAnnouncement('weekly_currency_drop', $createdEvent);

            $scheduledEvent->applyStatus(ScheduledEventStatus::RUNNING);
        } catch (Throwable $throwable) {
            $scheduledEvent->applyStatus(ScheduledEventStatus::FAILED);

            throw $throwable;
        }
    }
}
