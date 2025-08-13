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

class WinterEventEnderService implements EventEnder
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
        return $type->isWinterEvent();
    }

    /**
     * @param  EventType  $type
     * @param  ScheduledEvent  $scheduled
     * @param  ActiveEvent  $current
     * @return void
     */
    public function end(EventType $type, ScheduledEvent $scheduled, ActiveEvent $current): void
    {
        $this->kingdomEventService->handleKingdomRewardsForEvent(\App\Flare\Values\MapNameValue::ICE_PLANE);

        $iceMap = GameMap::query()->where('name', \App\Flare\Values\MapNameValue::ICE_PLANE)->first();

        if (is_null($iceMap)) {
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

        $faction = Faction::query()->where('game_map_id', $iceMap->id)->first();

        $this->mover->forCharactersOnMap($iceMap->id, function ($characters) use ($iceMap, $surfaceMap, $faction) {
            $this->mover->stopExplorationFor($characters);

            $this->mover->resetFactionProgressForMap($characters, $iceMap->id);

            $this->mover->moveAllToSurface($characters, $surfaceMap);

            foreach ($characters as $character) {
                $this->pledgeService->unpledgeIfOnFaction($character, $faction);
            }
        });

        event(new GlobalMessageEvent('The Queen of Ice calls forth her twisted memories and magics to seal the gates to her realm. "My son! You have stolen the memories of my son!" She bellows as she banishes you and others from her realm!'));

        $this->announcementCleanup->deleteByEventId($current->id);

        $current->delete();

        $this->goalCleanup->truncateAll();

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
