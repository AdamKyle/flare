<?php

namespace App\Game\Kingdoms\Service;

use Illuminate\Support\Facades\Cache;
use App\Flare\Models\User;
use App\Game\Core\Traits\KingdomCache;
use App\Game\Kingdoms\Events\UpdateGlobalMap;
use App\Game\Kingdoms\Events\UpdateNPCKingdoms;
use App\Game\Kingdoms\Handlers\UpdateKingdomHandler;
use App\Game\Kingdoms\Jobs\KingdomSettlementLockout;
use App\Game\Maps\Events\UpdateMapDetailsBroadcast;
use App\Game\Maps\Services\LocationService;
use App\Game\Maps\Services\MovementService;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\Kingdom;
use Facades\App\Flare\Values\UserOnlineValue;
use App\Game\Messages\Events\ServerMessageEvent as GameServerMessageEvent;


class KingdomResourcesService {

    use KingdomCache;

    private UpdateKingdomHandler $updateKingdomHandler;

    private MovementService $movementService;

    private LocationService $locationService;

    /**
     * constructor
     *
     * @param UpdateKingdomHandler $updateKingdomHandler
     * @param MovementService $movementService
     * @param LocationService $locationService
     */
    public function __construct(UpdateKingdomHandler $updateKingdomHandler, MovementService $movementService, LocationService $locationService) {
        $this->updateKingdomHandler = $updateKingdomHandler;
        $this->movementService      = $movementService;
        $this->locationService      = $locationService;
    }

    /**
     * Set the kingdom to be updated.
     *
     * @param Kingdom $kingdom
     * @return KingdomResourceService
     */
    public function setKingdom(Kingdom $kingdom): KingdomResourcesService {
        $this->kingdom = $kingdom;

        return $this;
    }

    public function abandonKingdom(Kingdom $kingdom) {
        $character = $kingdom->character;

        $this->removeKingdomFromCache($character, $kingdom);

        $this->npcTookKingdom($character->user, $kingdom->refresh());

        foreach ($kingdom->buildings as $building) {
            $newDurability = $building->current_durability - $building->current_durability * 0.35;

            if ($newDurability < 0) {
                $newDurability = 0;
            }

            $building->update([
                'current_durability' => $newDurability,
            ]);
        }

        foreach ($kingdom->units as $unit) {
            $newAmount = $unit->amount - $unit->amount * 0.75;

            if ($newAmount < 0) {
                $newAmount = 0;
            }

            $unit->update([
                'amount' => $newAmount,
            ]);
        }

        $newPopulation = $kingdom->current_population - $kingdom->current_population * 0.75;

        if ($newPopulation < 0) {
            $newPopulation = 0;
        }

        $kingdom->update([
            'character_id'       => null,
            'npc_owned'          => true,
            'current_morale'     => 0.10,
            'treasury'           => 0,
            'last_walked'        => now(),
            'current_population' => $newPopulation
        ]);

        $kingdom = $kingdom->refresh();

        if (!is_null($character->can_settle_again_at)) {
            $time = $character->can_settle_again_at->addMinutes(30);
        } else {
            $time = now()->addMinutes(30);
        }

        $character->update([
            'can_settle_again_at' => $time
        ]);

        $character = $character->refresh();

        KingdomSettlementLockout::dispatch($character)->delay(now()->addMinutes(15));

        $minutes = now()->diffInMinutes($time);

        event(new GameServerMessageEvent($character->user, 'You have been locked out of making a new kingdom for: '. $minutes . ' Minutes.'));

        broadcast(new UpdateNPCKingdoms($kingdom->gameMap));
        broadcast(new UpdateGlobalMap($character));
        broadcast(new UpdateMapDetailsBroadcast($character->map, $character->user, $this->movementService, true));
    }

    protected function putUpdatedKingdomIntoCache(array $cache = []): array {
        $isNpcOwned = Kingdom::find($this->kingdom->id)->npc_owned;

        if ($isNpcOwned) {
            // @codeCoverageIgnoreStart
            $key = array_search($this->kingdom->id, $cache);

            if ($key !== false) {
                unset($cache[$key]);
            }
            // @codeCoverageIgnoreEnd
        } else {
            $cache[] = $this->kingdom->id;
        }

        return $cache;
    }

    private function npcTookKingdom(User $user, Kingdom $kingdom) {
        $this->removeKingdomFromCache($user->character, $kingdom);

        event(new GlobalMessageEvent('A kingdom has fallen into the rubble at (X/Y): ' . $kingdom->x_position . '/' . $kingdom->y_position . ' on the: ' . $kingdom->gameMap->name .' plane.'));

        if (UserOnlineValue::isOnline($user)) {
            event(new ServerMessageEvent($user, 'kingdom-resources-update', $kingdom->name . ' Has been given to the NPC due to being abandoned, at Location (x/y): ' . $kingdom->x_position . '/' . $kingdom->y_position . ' on the: ' . $kingdom->gameMap->name . ' plane.'));
        } else {
            $this->updateKingdomCache($user, $kingdom);
        }
    }

    private function updateKingdomCache(User $user, Kingdom $kingdom) {
        if (Cache::has('kingdoms-updated-' . $user->id)) {
            // @codeCoverageIgnoreStart
            $cache = Cache::get('kingdoms-updated-' . $user->id);

            $cache = $this->putUpdatedKingdomIntoCache($cache);
            // @codeCoverageIgnoreEnd
        } else {
            $cache = $this->putUpdatedKingdomIntoCache();
        }

        Cache::put('kingdoms-updated-' . $user->id, $cache);
    }
}
