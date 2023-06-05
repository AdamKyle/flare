<?php

namespace App\Flare\Services;

use App\Flare\Models\Event;
use Illuminate\Support\Facades\Cache;
use App\Flare\Models\Quest;
use App\Flare\Models\Raid;

class BuildQuestCacheService {

    public function buildQuestCache(): void {
        $quests = Quest::where('is_parent', true)->whereNull('raid_id')->with('childQuests')->get();

        Cache::put('game-quests', $quests->toArray());
    }

    public function buildRaidQuestCache(): void {
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
