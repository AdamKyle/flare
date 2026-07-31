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

class InitiateWeeklyFactionLoyaltyEvent implements ShouldQueue
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
                'type' => EventType::WEEKLY_FACTION_LOYALTY_EVENT,
                'started_at' => $scheduledEvent->start_date,
                'ends_at' => $scheduledEvent->end_date,
                'scheduled_event_id' => $scheduledEvent->id,
            ]);

            event(new GlobalMessageEvent('Weekly Faction Loyalty Event has started. Players, for the next 24 hours, can gain 2 points in any task
        they ar doing for the NPC. When NPC\'s tasks refresh, they will refresh with half the required amount for each task.'));

            AnnouncementHandler::createAnnouncement('weekly_faction_loyalty_event', $createdEvent);

            $scheduledEvent->applyStatus(ScheduledEventStatus::RUNNING);
        } catch (Throwable $throwable) {
            $scheduledEvent->applyStatus(ScheduledEventStatus::FAILED);

            throw $throwable;
        }
    }
}
