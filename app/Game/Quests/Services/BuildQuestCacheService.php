<?php

namespace App\Game\Quests\Services;

use App\Flare\Models\Event;
use App\Flare\Models\Quest;
use App\Flare\Models\Raid;
use App\Game\Events\Values\EventType;
use App\Game\Quests\Events\UpdateQuests;
use App\Game\Quests\Events\UpdateRaidQuests;
use App\Game\Quests\Transformers\QuestTransformer;
use Illuminate\Support\Facades\Cache;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;

class BuildQuestCacheService
{
    public function __construct(private QuestTransformer $questTransformer, private Manager $manager) {}

    public function buildQuestCache(bool $sendOffEvent = false): void
    {
        $quests = Quest::where('is_parent', true)
            ->whereNull('only_for_event')
            ->whereNull('raid_id')
            ->with('childQuests')
            ->get();

        $quests = new Collection($quests, $this->questTransformer);
        $quests = $this->manager->createData($quests)->toArray();

        $eventQuests = [];
        $events = [EventType::WINTER_EVENT, EventType::DELUSIONAL_MEMORIES_EVENT];

        foreach ($events as $event) {
            $eventQuests = array_merge($eventQuests, $this->fetchEventQuests($event));
        }

        $quests = array_merge($quests, $eventQuests);

        Cache::put('game-quests', $quests);

        if ($sendOffEvent) {
            event(new UpdateQuests($quests));
        }
    }

    protected function fetchEventQuests(string $eventType): array
    {
        $event = Event::where('type', $eventType)->first();

        if (is_null($event)) {
            return [];
        }

        $quests = Quest::where('is_parent', true)
            ->where('only_for_event', $eventType)
            ->whereNull('raid_id')
            ->with('childQuests')
            ->get();

        $quests = new Collection($quests, $this->questTransformer);

        return $this->manager->createData($quests)->toArray();
    }

    public function buildRaidQuestCache(bool $sendOffEvent = false): void
    {
        $raids = Raid::all();
        $raidQuests = [];

        foreach ($raids as $raid) {
            $quests = Quest::where('is_parent', true)
                ->where('raid_id', $raid->id)
                ->with('childQuests')
                ->get();

            $quests = new Collection($quests, $this->questTransformer);
            $quests = $this->manager->createData($quests)->toArray();

            $raidQuests[$raid->id] = $quests;
        }

        Cache::put('raid-quests', $raidQuests);

        if ($sendOffEvent) {

            $event = Event::whereNotNull('raid_id')->first();

            $quests = $this->fetchQuestsForRaid($event);

            event(new UpdateRaidQuests($quests));
        }
    }

    public function getRegularQuests(): ?array
    {
        return Cache::get('game-quests');
    }

    public function getRaidQuests(): ?array
    {
        return Cache::get('raid-quests');
    }

    public function fetchQuestsForRaid(?Event $eventWithRaid = null): array
    {
        $eventQuests = [];

        if (is_null($eventWithRaid)) {
            return $eventQuests;
        }

        $raidQuests = $this->getRaidQuests();

        if (is_null($raidQuests)) {
            return $eventQuests;
        }

        if (! isset($raidQuests[$eventWithRaid->raid_id])) {
            return $eventQuests;
        }

        return $raidQuests[$eventWithRaid->raid_id];
    }
}
