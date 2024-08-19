<?php

namespace App\Game\Events\Jobs;

use App\Flare\Models\Event;
use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;
use Facades\App\Game\Core\Handlers\AnnouncementHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class InitiateFeedbackEvent implements ShouldQueue
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

        $event = ScheduledEvent::find($this->eventId);

        if (is_null($event)) {

            return;
        }

        $event->update([
            'currently_running' => true,
        ]);

        Event::create([
            'type' => EventType::FEEDBACK_EVENT,
            'started_at' => now(),
            'ends_at' => now()->addDay(),
        ]);

        event(new GlobalMessageEvent('The feedback event has started!' . ' '.
            'Players who are new and old will gain 75 more xp per kill under level 1,000, 150 more xp under level 5000 and for those who have reincarnated, you will gain 500 more xp per kill.'. ' '.
            'Players will also gain +150 XP in training skills and in crafting skills, including alchemy and enchanting, they will also see a raise of +175xp per craft/enchant!'.' '.
            'After 6 hours of combined (does NOT need to be consecutive) - players of all skill types and play times will be asked to participate in a survey to help Tlessa become a better game. Once you complete the survey you will be rewarded with a mythical item!'. ' '
        ));

        AnnouncementHandler::createAnnouncement('tlessas_feedback_event');
    }
}
