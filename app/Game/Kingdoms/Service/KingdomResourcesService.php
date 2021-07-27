<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Character;
use App\Flare\Models\KingdomBuilding;
use App\Flare\Models\User;
use App\Game\Core\Traits\KingdomCache;
use App\Game\Kingdoms\Events\UpdateEnemyKingdomsMorale;
use App\Game\Kingdoms\Events\UpdateGlobalMap;
use App\Game\Kingdoms\Events\UpdateNPCKingdoms;
use App\Game\Maps\Events\UpdateMapDetailsBroadcast;
use App\Game\Maps\Services\MovementService;
use App\Game\Messages\Events\GlobalMessageEvent;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\Kingdom;
use App\Flare\Transformers\KingdomTransformer;
use Facades\App\Flare\Values\UserOnlineValue;
use App\Game\Kingdoms\Events\UpdateKingdom;
use Cache;

class KingdomResourcesService {

    use KingdomCache;

    /**
     * @var Kingdom $kingdom
     */
    private $kingdom;

    /**
     * @var Manager $manager
     */
    private $manager;

    /**
     * @var KingdomTransformer $kingdomTransformer
     */
    private $kingdomTransformer;

    private $movementService;

    /**
     * @var array $kingdomsUpdated
     */
    private $kingdomsUpdated = [];

    private $doNotNotify = false;

    /**
     * constructor
     *
     * @param Manager $manager
     * @param KingdomTransformer $kingdomTransformer
     * @return void
     */
    public function __construct(Manager $manager, KingdomTransformer $kingdomTransformer, MovementService $movementService) {
        $this->manager            = $manager;
        $this->kingdomTransformer = $kingdomTransformer;
        $this->movementService    = $movementService;
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

    /**
     * Updates the kingdoms resources.
     *
     * Will updates cores resourses, population and morale.
     *
     * This will also alert the player if they are online, via the chat are.
     *
     * @return void
     */
    public function updateKingdom(): void {
        if ($this->kingdom->npc_owned) {
            return;
        }

        // If the kingdom has never been walked, take it.
        if (is_null($this->kingdom->last_walked)) {
            $this->giveNPCKingdoms();

            $this->doNotNotify = true;

            return;
        }

        $lastTimeWalked = $this->kingdom->last_walked->diffInDays(now());

        $this->increaseOrDecreaseMorale($lastTimeWalked);

        if ($lastTimeWalked < 5) {
            $this->updateCurrentPopulation();
            $this->increaseCurrentResource();
            $this->increaseTreasury();
        }

        if (!$this->doNotNotify) {
            $this->notifyUser();
        }

        $this->doNotNotify = false;
    }

    /**
     * Gets the details pertaining to the kingdoms that were updated.
     */
    public function getKingdomsUpdated(): array {
        return $this->kingdomsUpdated;
    }

    /**
     * Increase or decrease the morale.
     *
     * This is based on building durability.
     */
    public function increaseOrDecreaseMorale(int $lastWalked): void {
        $totalIncrease = 0;
        $totalDecrease = 0;
        $buildings     = $this->kingdom->buildings;
        $currentMorale = $this->kingdom->current_morale;

        if ($lastWalked > 5) {

            if ($currentMorale <= 0.0) {
                $this->giveNPCKingdoms();

                $this->doNotNotify = true;

                return;
            }

            $this->kingdom->update([
                'current_morale' => $currentMorale - 0.10,
            ]);

            $message = $this->kingdom->name . ' is loosing morale, due to not being walked for more then 5 days, at Location (x/y): ' . $this->kingdom->x_position . '/' . $this->kingdom->y_position;

            $this->notifyUser($message);

            $this->doNotNotify = true;

            return;
        }

        foreach ($buildings as $building) {
            if ($building->current_durability > 0) {
                $totalIncrease += $building->morale_increase;
            } else {
                $totalDecrease += $building->morale_decrease;
            }
        }

        if ($totalIncrease > $totalDecrease) {
            $totalIncrease -= $totalDecrease;

            $this->addMorale($totalIncrease);

            return;
        } else if ($totalIncrease < $totalDecrease) {
            $totalDecrease -= $totalIncrease;

            $this->reduceMorale($totalDecrease);

            return;
        }

        $this->adjustMorale($totalIncrease, $totalDecrease);
    }

    protected function giveNPCKingdoms() {
        $character = $this->kingdom->character;

        $this->kingdom->update([
            'character_id'   => null,
            'npc_owned'      => true,
            'current_morale' => 0.10
        ]);

        $this->npcTookKingdom($character->user, $this->kingdom);

        broadcast(new UpdateNPCKingdoms($this->kingdom->gameMap));
        broadcast(new UpdateGlobalMap($character));
        broadcast(new UpdateMapDetailsBroadcast($character->map, $character->user, $this->movementService, true));
    }

    protected function putUpdatedKingdomIntoCache(int $kingdomId, array $cache = []): array {
        $isNpcOwned = Kingdom::find($kingdomId)->npc_owned;

        if ($isNpcOwned) {
            $key = array_search($kingdomId, $cache);

            if ($key !== false) {
                unset($cache[$key]);
            }
        } else {
            $cache[] = $kingdomId;
        }


        return $cache;
    }

    protected function updateCurrentPopulation() {
        $building = $this->kingdom->buildings->where('is_farm', true)->first();
        $morale   = $this->kingdom->current_morale;

        if ($morale === 0 || $morale === 0.0) {
            return;
        }

        if ($building->current_durability === 0) {
            if ($this->kingdom->current_population !== $this->kingdom->max_population) {
                $this->kingdom->update([
                    'current_population' => $this->kingdom->current_population + 10,
                ]);
            }

            return;
        }

        if (!is_null($building)) {
            $newCurrent = $this->kingdom->current_population + round($building->population_increase / 2);

            if ($newCurrent > $this->kingdom->max_population) {
                $newCurrent = $this->kingdom->max_population;
            }

            $this->kingdom->update([
                'current_population' => $newCurrent,
            ]);
        }

        $this->kingdom = $this->kingdom->refresh();
    }

    protected function increaseCurrentResource() {
        $resources = ['wood', 'clay', 'stone', 'iron'];

        foreach($resources as $resource) {
            $building = $this->kingdom->buildings->where('gives_resources', true)->where('increase_in_'.$resource)->first();
            $morale   = $this->kingdom->morale;

            if ($building->current_durability === 0) {
                if ($morale === 0) {
                    continue;
                } else {
                    $this->increaseResource($resource, $building);
                }
            }

            if (!is_null($building)) {
                $this->increaseResource($resource, $building);
            }
        }

        $this->kingdom = $this->kingdom->refresh();
    }

    protected function increaseResource(string $resource, KingdomBuilding  $building) {
        $newCurrent = $this->kingdom->{'current_' . $resource} + $building->{'increase_in_'.$resource};

        if ($newCurrent > $this->kingdom->{'max_' . $resource}) {
            $newCurrent = $this->kingdom->{'max_' . $resource};
        }

        $this->kingdom->{'current_' . $resource} = $newCurrent;

        $this->kingdom->save();
    }

    protected function increaseTreasury() {
        if ($this->kingdom->current_morale === 0.0) {
            return;
        }

        if ($this->kingdom->current_morale > 0.50) {
            return $this->updateTreasury(1000);
        }

        return $this->updateTreasury(100);
    }


    protected function addMorale(float $toAdd): void {
        $current = $this->kingdom->current_morale;

        if ($current === 1.0) {
            return;
        }

        $newTotal = $current + $toAdd;

        if ($newTotal >= 1.0) {
            $this->kingdom->update([
                'current_morale' => 1.0
            ]);
        } else {
            $this->kingdom->update([
                'current_morale' => $newTotal,
            ]);
        }

        $this->kingdom = $this->kingdom->refresh();
    }

    protected function reduceMorale(float $toSub): void {
        $current = $this->kingdom->current_morale;

        if ($current === 0.0) {
            return;
        }

        $newTotal = $current - $toSub;

        if ($newTotal <= 0.0) {
            $this->kingdom->update([
                'current_morale' => 0.0
            ]);
        } else {
            $this->kingdom->update([
                'current_morale' => $newTotal,
            ]);
        }

        $this->kingdom = $this->kingdom->refresh();
    }

    protected function adjustMorale(float $toAdd, float $toSub): void {
        $current = $this->kingdom->current_morale;

        $newTotal = ($current + $toAdd) - $toSub;

        $this->kingdom->update([
            'current_morale' => $newTotal,
        ]);

        $this->kingdom = $this->kingdom->refresh();
    }

    private function updateTreasury(int $increase) {
        $this->kingdom->update([
            'treasury' => $this->kingdom->treasury + $increase,
        ]);

        $this->kingdom = $this->kingdom->refresh();
    }

    private function notifyUser(string $message = null) {
        $user    = $this->kingdom->character->user;
        $kingdom = $this->kingdom;

        if (UserOnlineValue::isOnline($user)) {

            broadcast(new UpdateEnemyKingdomsMorale($kingdom));

            $kingdom = new Item($kingdom, $this->kingdomTransformer);
            $kingdom = $this->manager->createData($kingdom)->toArray();

            event(new UpdateKingdom($user, $kingdom));

            if ($user->show_kingdom_update_messages) {
                $serverMessage = $this->kingdom->name . ' Has updated it\'s resources at Location (x/y): ' . $this->kingdom->x_position . '/' . $this->kingdom->y_position;

                if (!is_null($message)) {
                    $serverMessage = $message;
                }

                event(new ServerMessageEvent($user, 'kingdom-resources-update', $serverMessage));
            }
        } else {
            $this->updateKingdomCache($user, $kingdom);
        }

        broadcast(new UpdateNPCKingdoms($this->kingdom->gameMap));
        broadcast(new UpdateGlobalMap($user->character));
    }

    private function npcTookKingdom(User $user, Kingdom $kingdom) {
        $this->removeKingdomFromCache($user->character, $kingdom);

        event(new GlobalMessageEvent('A kingdom has fallen into the rubble at (X/Y): ' . $this->kingdom->x_position . '/' . $this->kingdom->y_position . ' on the: ' . $this->kingdom->gameMap->name .' plane.'));

        if (UserOnlineValue::isOnline($user)) {
            event(new ServerMessageEvent($user, 'kingdom-resources-update', $this->kingdom->name . ' Has been given to the NPC due to being abandoned, at Location (x/y): ' . $this->kingdom->x_position . '/' . $this->kingdom->y_position));
        } else {
            $this->updateKingdomCache($user, $kingdom);
        }
    }

    private function updateKingdomCache(User $user, Kingdom $kingdom) {
        if (Cache::has('kingdoms-updated-' . $user->id)) {
            $cache = Cache::get('kingdoms-updated-' . $user->id);

            $cache = $this->putUpdatedKingdomIntoCache($kingdom->id);

            Cache::put('kingdoms-updated-' . $user->id, $cache);
        } else {
            $cache = $this->putUpdatedKingdomIntoCache($kingdom->id);

            Cache::put('kingdoms-updated-' . $user->id, $cache);
        }
    }
}
