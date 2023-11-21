<?php

namespace App\Game\Events\Jobs;

use App\Flare\Models\Event;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventForEventTypeValue;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Quests\Services\BuildQuestCacheService;
use Facades\App\Game\Core\Handlers\AnnouncementHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class InitiateWinterEvent implements ShouldQueue {

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var int $eventId
     */
    protected int $eventId;

    /**
     * Create a new job instance.
     *
     * @param int $eventId
     */
    public function __construct(int $eventId) {
        $this->eventId = $eventId;
    }

    /**
     * @return void
     */
    public function handle(BuildQuestCacheService $buildQuestCacheService): void {

        $event = ScheduledEvent::find($this->eventId);

        if (is_null($event)) {

            return;
        }

        $event->update([
            'currently_running' => true,
        ]);

        Event::create([
            'type'       => EventType::WINTER_EVENT,
            'started_at' => now(),
            'ends_at'    => $event->end_date
        ]);

        event(new GlobalMessageEvent('A winter chill sets over you. You turn and see the gates to the Ice Queens Realm is open. Do you dare enter? (Players just have to traverse to the new plane, you can do with this the traverse on desktop or Map Movement -> Traverse on Mobile.)'));

        AnnouncementHandler::createAnnouncement('winter_event');

        $buildQuestCacheService->buildQuestCache(true);

        $this->kickOffGlobalEventGoal();

        event(new GlobalMessageEvent('Players who have Guide Quests enabled will also see a set of new quests to introduce them to the Winter Event. These are geared at new and existing players.'));
    }

    public function kickOffGlobalEventGoal() {
        $globalEventGoalData = GlobalEventForEventTypeValue::returnGlobalEventInfoForSeasonalEvents(EventType::WINTER_EVENT);

        GlobalEventGoal::create($globalEventGoalData);

        event(new GlobalMessageEvent('While on the The Ice Plane, characters who kill: ANY CREATURE in either manual or exploration, will increase the new: Global Event Goal. Players will be rewarded with random Corrupted Ice Gear when specific milestones are reached.'));
    }
}
