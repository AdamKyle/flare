<?php

namespace App\Game\Events\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Event as ActiveEvent;
use App\Flare\Models\Faction;
use App\Flare\Models\GameMap;
use App\Flare\Models\ScheduledEvent;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Events\Services\Concerns\EventEnder;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Quests\Services\BuildQuestCacheService;

class DelusionalMemoriesEventEnderService implements EventEnder
{

    /**
     * @param  KingdomEventService  $kingdomEventService
     * @param  MoveCharacterAfterEventService  $mover
     * @param  FactionLoyaltyPledgeCleanupService  $pledgeService
     * @param  AnnouncementCleanupService  $announcementCleanup
     * @param  GlobalEventGoalCleanupService  $goalCleanup
     */
    public function __construct(
        private readonly KingdomEventService $kingdomEventService,
        private readonly MoveCharacterAfterEventService $mover,
        private readonly FactionLoyaltyPledgeCleanupService $pledgeService,
        private readonly AnnouncementCleanupService $announcementCleanup,
        private readonly GlobalEventGoalCleanupService $goalCleanup
    ) { }


    /**
     * @param  EventType  $type
     * @return bool
     */
    public function supports(EventType $type): bool
    {
        return $type->isDelusionalMemoriesEvent();
    }

    /**
     * @param  EventType  $type
     * @param  ScheduledEvent  $scheduled
     * @param  ActiveEvent  $current
     * @return void
     */
    public function end(EventType $type, ScheduledEvent $scheduled, ActiveEvent $current): void
    {
        $this->kingdomEventService->handleKingdomRewardsForEvent(\App\Flare\Values\MapNameValue::DELUSIONAL_MEMORIES);

        $map = GameMap::query()->where('name', \App\Flare\Values\MapNameValue::DELUSIONAL_MEMORIES)->first();

        if (is_null($map)) {
            $this->announcementCleanup->deleteByEventId($current->id);
            $current->delete();
            return;
        }

        $surfaceMap = GameMap::query()->where('name', \App\Flare\Values\MapNameValue::SURFACE)->first();

        if (is_null($surfaceMap)) {
            $this->announcementCleanup->deleteByEventId($current->id);
            $current->delete();
            return;
        }

        $faction = Faction::query()->where('game_map_id', $map->id)->first();

        $this->mover->forCharactersOnMap($map->id, function ($characters) use ($map, $surfaceMap, $faction) {
            $this->mover->stopExplorationFor($characters);

            $this->mover->resetFactionProgressForMap($characters, $map->id);

            $this->mover->moveAllToSurface($characters, $surfaceMap);

            foreach ($characters as $character) {
                $this->pledgeService->unpledgeIfOnFaction($character, $faction);
            }
        });

        event(new GlobalMessageEvent('The voice of Fliniguss echos in your ears: "Child, I grow weary of your games." The twisted mother laughs: Ooooh hooo hooo hoo. A chill falls in the air.'));

        $this->announcementCleanup->deleteByEventId($current->id);

        $current->delete();

        $this->goalCleanup->purgeCoreAndGoal();
        $this->goalCleanup->purgeEnchantInventories();

        $this->updateAllCharacterStatuses();

        app(BuildQuestCacheService::class)->buildQuestCache(true);
        app(BuildQuestCacheService::class)->buildRaidQuestCache(true);
    }

    /**
     * @return void
     */
    private function updateAllCharacterStatuses(): void
    {
        Character::chunkById(250, function ($characters) {
            foreach ($characters as $character) {
                event(new UpdateCharacterStatus($character));
            }
        });
    }
}
