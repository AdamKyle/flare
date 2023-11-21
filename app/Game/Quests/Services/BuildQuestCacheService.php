<?php

namespace App\Game\Quests\Services;

use App\Flare\Models\Event;
use App\Flare\Models\Quest;
use App\Flare\Models\Raid;
use App\Game\Events\Values\EventType;
use App\Game\Quests\Events\UpdateQuests;
use App\Game\Quests\Events\UpdateRaidQuests;
use Illuminate\Support\Facades\Cache;

class BuildQuestCacheService {

    public function buildQuestCache(bool $sendOffEvent = false): void {
        $quests = Quest::where('is_parent', true)
            ->whereNull('only_for_event');

        if ($this->isWinterEventRunning()) {
            $quests = $quests->orWhere('only_for_event', EventType::WINTER_EVENT);
        }

        $quests = $quests->whereNull('raid_id')
            ->with('childQuests')
            ->get();

        $quests = $quests->toArray();

        Cache::put('game-quests', $quests);

        if ($sendOffEvent) {
            event(new UpdateQuests($quests));
        }
    }


    protected function isWinterEventRunning(): bool {
        return Event::where('type', EventType::WINTER_EVENT)->count() > 0;
    }

    public function buildRaidQuestCache(bool $sendOffEvent = false): void {
        $raids      = Raid::all();
        $raidQuests = [];

        foreach ($raids as $raid) {
            $quests = Quest::where('is_parent', true)
                ->where('raid_id', $raid->id)
                ->with('childQuests')
                ->get();

            $raidQuests[$raid->id] = $quests->toArray();
        }

        Cache::put('raid-quests', $raidQuests);

        if ($sendOffEvent) {
            event(new UpdateRaidQuests($raidQuests));
        }
    }

    public function getRegularQuests(): array|null {
        return Cache::get('game-quests');
    }

    public function getRaidQuests(): array|null {
        return Cache::get('raid-quests');
    }

    public function fetchQuestsForRaid(Event $eventWithRaid = null): array {
        $eventQuests   = [];

        if (!is_null($eventWithRaid)) {
            $raidQuests = $this->getRaidQuests();

            if (!is_null($raidQuests)) {

                if (isset($raidQuests[$eventWithRaid->raid_id])) {
                    $eventQuests = $raidQuests[$eventWithRaid->raid_id];
                }
            }
        }

        return $eventQuests;
    }
}
