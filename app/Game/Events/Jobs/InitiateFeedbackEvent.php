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

        $event = $event->refresh();

        Event::create([
            'type' => EventType::FEEDBACK_EVENT,
            'started_at' => $event->start_date,
            'ends_at' => $event->end_date,
        ]);

        event(new GlobalMessageEvent(
            'The feedback event has started! '.
            'New and returning players will gain +75 XP per kill under level 1,000, and +150 XP from Level 1,000 - 5,000. For those who have reincarnated, you will gain +500 XP per kill. '.
            'Players will also gain +150 XP in training skills such as Accuracy, Looting and so on, with a bonus of +175 XP per crafting skills such as trinketry, gem crafting, regular crafting and enchanting as well as other crafting skills '.
            'After 1 hour of combined playtime (does NOT need to be consecutive), players of all skill levels will be asked to participate in a survey to help make Tlessa a better game. Upon completing the survey, you will be rewarded with a Mythical Item!'
        ));

        AnnouncementHandler::createAnnouncement('tlessas_feedback_event');
    }
}
