<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Character;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\KingdomBuilding;
use App\Flare\Models\KingdomLog;
use App\Flare\Models\Session;
use App\Flare\Models\User;
use App\Game\Core\Traits\KingdomCache;
use App\Game\Kingdoms\Events\UpdateEnemyKingdomsMorale;
use App\Game\Kingdoms\Events\UpdateGlobalMap;
use App\Game\Kingdoms\Events\UpdateNPCKingdoms;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use App\Game\Maps\Events\UpdateMapDetailsBroadcast;
use App\Game\Maps\Services\MovementService;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Skills\Values\SkillTypeValue;
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
        if (is_null($this->kingdom->last_walked) && !$this->kingdom->npc_owned) {
            $this->giveNPCKingdoms();

            $this->doNotNotify = true;

        } else if (!is_null($this->kingdom->last_walked) && !$this->kingdom->npc_owned) {
            $lastTimeWalked = $this->kingdom->last_walked->diffInDays(now());

            if ($lastTimeWalked > 40) {
                $this->giveNPCKingdoms();

                return;
            }

            $this->increaseOrDecreaseMorale($lastTimeWalked);

            if ($lastTimeWalked < 30) {
                $this->updateCurrentPopulation();
                $this->increaseCurrentResource();
                $this->increaseTreasury();
            }

            if (!$this->doNotNotify) {
                $this->notifyUser();
            }

            $this->doNotNotify = false;
        } else if ($this->kingdom->npc_owned) {
            $lastTimeWalked = $this->kingdom->updated_at->diffInDays(now());

            if ($lastTimeWalked >= 5) {
                $this->removeKingdomFromMap();
            }
        }
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

        if ($lastWalked >= 30) {

            if ($currentMorale <= 0.0) {
                $this->giveNPCKingdoms(false);

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
        }

        $totalDecrease -= $totalIncrease;

        $this->reduceMorale($totalDecrease);
    }

    public function giveNPCKingdoms(bool $notify = true) {
        $character = $this->kingdom->character;

        if (!$notify) {
            $this->removeKingdomFromCache($character, $this->kingdom->refresh());
        } else {
            $this->npcTookKingdom($character->user, $this->kingdom);
        }

        $this->kingdom->update([
            'character_id'   => null,
            'npc_owned'      => true,
            'current_morale' => 0.10,
            'last_walked'    => now(),
        ]);

        broadcast(new UpdateNPCKingdoms($this->kingdom->gameMap));
        broadcast(new UpdateGlobalMap($character));
        broadcast(new UpdateMapDetailsBroadcast($character->map, $character->user, $this->movementService, true));
    }

    /**
     * Remove the kingdom from the map.
     *
     * We can't actually test this method due to the fact that it breaks the Refresh Database transaction
     * do the fact that we actively commit the changes.
     *
     * @codeCoverageIgnore
     */
    protected function removeKingdomFromMap() {
        $x     = $this->kingdom->x_position;
        $y     = $this->kingdom->y_position;
        $plane = $this->kingdom->gameMap->name;

        KingdomLog::where('from_kingdom_id', $this->kingdom->id)->delete();
        KingdomLog::where('to_kingdom_id', $this->kingdom->id)->delete();


        $this->kingdom->buildingsQueue()->truncate();
        $this->kingdom->unitsMovementQueue()->truncate();
        $this->kingdom->unitsQueue()->truncate();
        $this->kingdom->units()->delete();
        $this->kingdom->buildings()->delete();

        $this->kingdom->refresh()->delete();

        $this->alertUsersOfKingdomRemoval();

        broadcast(new GlobalMessageEvent('A kingdom at: (X/Y) ' . $x . '/' . $y . ' on ' .$plane .' Plane has crumbled to the earth clearing up space for a new kingdom'));
    }

    /**
     * Alert the user their kingdom was removed via server message.
     *
     * This method is only called in the removeKingdomFromMap function,
     * and since we cannot test protected methods, nor do I want this public,
     * we turn off code coverage for it.
     *
     * @codeCoverageIgnore
     */
    protected function alertUsersOfKingdomRemoval() {
        UserOnlineValue::getUsersOnlineQuery()->chunkById(100, function($sessions) {
            foreach ($sessions as $session) {
                $user = User::find($session->user_id);

                if (!$user->hasRole('Admin')) {
                    $character = $user->character;

                    broadcast(new UpdateGlobalMap($character));
                    broadcast(new UpdateMapDetailsBroadcast($character->map, $character->user, $this->movementService, true));
                }
            }
        });
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

    protected function updateCurrentPopulation() {
        $building = $this->kingdom->buildings->where('is_farm', true)->first();
        $morale   = $this->kingdom->current_morale;

        if ($morale === 0 || $morale === 0.0) {

            $newAmount = $this->kingdom->current_population + 30;

            if ($newAmount > $this->kingdom->max_population) {
                $newAmount = $this->kingdom->max_population;
            }

            $this->kingdom->update([
                'current_population' => $newAmount,
            ]);

            $this->kingdom = $this->kingdom->refresh();

            return;
        }

        if ($building->current_durability === 0) {
            $newAmount = $this->kingdom->current_population + round($building->population_increase/ 2);

            if ($newAmount > $this->kingdom->max_population) {
                $newAmount = $this->kingdom->max_population;
            }

            $this->kingdom->update([
                'current_population' => $newAmount,
            ]);

            $this->kingdom = $this->kingdom->refresh();

            return;
        }

        if (!is_null($building)) {
            $newCurrent = $this->kingdom->current_population + $building->population_increase;

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
            $morale   = $this->kingdom->current_morale;

            if ($building->current_durability === 0) {
                if ($morale === 0.0) {
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

        if (KingdomMaxValue::isTreasuryAtMax($this->kingdom)) {
             return;
        }

        if ($this->kingdom->current_morale > 0.50) {
            $characterSkill = $this->kingdom->character->skills->filter(function($skill) {
                return $skill->baseSkill->type === SkillTypeValue::EFFECTS_KINGDOM_TREASURY;
            })->first();

            $currentTreasury = $this->kingdom->treasury;

            $keep = $this->kingdom->buildings()
                                  ->where('game_building_id', GameBuilding::where('name', 'Keep')->first()->id)
                                  ->first();

            $total = $currentTreasury + $currentTreasury * ($characterSkill->skill_bonus + ($keep->level / 100));

            if ($total === 0 || $total === 0.0) {
                $total = 1;
            }

            return $this->updateTreasury($total);
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

    private function updateTreasury(int $increase) {

        if ($increase >= KingdomMaxValue::MAX_TREASURY) {
            $increase = KingdomMaxValue::MAX_TREASURY;
        }

        $this->kingdom->update([
            'treasury' => $increase,
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
