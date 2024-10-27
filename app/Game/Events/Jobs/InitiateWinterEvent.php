<?php

namespace App\Game\Events\Jobs;

use App\Flare\Models\Event;
use App\Flare\Models\GameMap;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Values\MapNameValue;
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

class InitiateWinterEvent implements ShouldQueue
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

    public function handle(BuildQuestCacheService $buildQuestCacheService): void
    {

        $event = ScheduledEvent::find($this->eventId);

        if (is_null($event) || $event->currently_running) {

            return;
        }

        $event->update([
            'currently_running' => true,
        ]);

        $event = $event->refresh();

        Event::create([
            'type' => EventType::WINTER_EVENT,
            'started_at' => $event->start_date,
            'ends_at' => $event->end_date,
        ]);

        event(new GlobalMessageEvent('A winter chill sets over you. You turn and see the gates to the Ice Queens Realm is open. Do you dare enter? (Players just have to traverse to the new plane, you can do with this the traverse on desktop or Map Movement -> Traverse on Mobile.)'));

        AnnouncementHandler::createAnnouncement('winter_event');

        $this->kickOffGlobalEventGoal();

        event(new GlobalMessageEvent('Players who have Guide Quests enabled will also see a set of new quests to introduce them to the Winter Event. These are geared at new and existing players.'));

        $buildQuestCacheService->buildQuestCache(true);
    }

    public function kickOffGlobalEventGoal(): void
    {
        $globalEventGoalData = GlobalEventForEventTypeValue::returnGlobalEventInfoForSeasonalEvents(EventType::WINTER_EVENT);

        GlobalEventGoal::create($globalEventGoalData);

        $gameMap = GameMap::where('name', MapNameValue::ICE_PLANE)->first();

        event(new GlobalMessageEvent('While on the The Ice Plane, characters who kill: ANY CREATURE in either manual or exploration, will increase the new: Global Event Goal. Players will be rewarded with random Corrupted Ice Gear when specific milestones are reached.'));

        event(new GlobalMessageEvent('Players can participate by going to the map: '.$gameMap->name.
            ' via Traverse (under the map for desktop, under the map inside Map Movement action drop down for mobile)'.' '.
            'And completing either Fighting monsters, Crafting: Weapons, Spells, Armour and Rings or enchanting the already crafted items.'.
            ' You can see the event goal for the map specified by being on the map and clicking the Event Goal tab from the map.'));
    }
}
