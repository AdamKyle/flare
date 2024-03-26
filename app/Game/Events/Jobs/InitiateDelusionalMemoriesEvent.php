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

class InitiateDelusionalMemoriesEvent implements ShouldQueue {

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
     * @param BuildQuestCacheService $buildQuestCacheService
     * @return void
     */
    public function handle(BuildQuestCacheService $buildQuestCacheService): void {

        $event = ScheduledEvent::find($this->eventId);

        if (is_null($event) || $event->currently_running) {
            return;
        }

        $event->update([
            'currently_running' => true,
        ]);

        Event::create([
            'type'       => EventType::DELUSIONAL_MEMORIES_EVENT,
            'started_at' => now(),
            'ends_at'    => $event->end_date
        ]);

        event(new GlobalMessageEvent('The twisted and delusional laughter of a mad man haunts your ears: Fliniguss\'s realm opens to those who dare to delve the delusional memories of a mad man,'));

        AnnouncementHandler::createAnnouncement('delusional_memories_event');

        $this->kickOffGlobalEventGoal();

        event(new GlobalMessageEvent('Players who have Guide Quests enabled will also see a set of new quests to introduce them to the Delusional Memories Event. These are geared at new and existing players.'));

        $buildQuestCacheService->buildQuestCache(true);
    }

    /**
     * @return void
     */
    public function kickOffGlobalEventGoal(): void {
        $globalEventGoalData = GlobalEventForEventTypeValue::returnGlobalEventInfoForSeasonalEvents(EventType::WINTER_EVENT);

        GlobalEventGoal::create($globalEventGoalData);

        event(new GlobalMessageEvent('"Child! We need you!" The Red Hawk Soldier looks at you. There is a fear in his eyes. "Please child. Fight with us!"'));

        event(new GlobalMessageEvent('While on the Delusional Memories Plane, characters who kill: ANY CREATURE in either manual or exploration, will increase the new: Global Event Goal. ' .
            'Players will be rewarded with random Corrupted Ice Gear when specific milestones are reached. ' .
            'Players who participate and help the battle progress, will move the event forward to a crafting and then enchanting and then back to fighting - and around we go again.'));
    }
}
