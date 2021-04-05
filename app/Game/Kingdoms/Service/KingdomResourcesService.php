<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\KingdomBuilding;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\Kingdom;
use App\Flare\Transformers\KingdomTransformer;
use Facades\App\Flare\Values\UserOnlineValue;
use App\Game\Kingdoms\Events\UpdateKingdom;
use Cache;

class KingdomResourcesService {

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

    /**
     * @var array $kingdomsUpdated
     */
    private $kingdomsUpdated = [];

    /**
     * constructor
     *
     * @param Manager $manager
     * @param KingdomTransformer $kingdomTransformer
     * @return void
     */
    public function __construct(Manager $manager, KingdomTransformer $kingdomTransformer) {
        $this->manager            = $manager;
        $this->kingdomTransformer = $kingdomTransformer;
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
        $this->increaseOrDecreaseMorale();
        $this->updateCurrentPopulation();
        $this->increaseCurrentResource();
        $this->increaseTreasury();

        $user  = $this->kingdom->character->user;
        $kingdom = $this->kingdom;

        if (UserOnlineValue::isOnline($user)) {
            $kingdom = new Item($kingdom, $this->kingdomTransformer);
            $kingdom = $this->manager->createData($kingdom)->toArray();

            event(new UpdateKingdom($user, $kingdom));
            event(new ServerMessageEvent($user, 'kingdom-resources-update', $this->kingdom->name . ' Has updated it\'s resources at Location (x/y): ' . $this->kingdom->x_position . '/' . $this->kingdom->y_position));
        } else {
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
    public function increaseOrDecreaseMorale(): void {
        $totalIncrease = 0;
        $totalDecrease = 0;
        $buildings     = $this->kingdom->buildings;

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

    protected function putUpdatedKingdomIntoCache(int $kingdomId, array $cache = []): array {
        $cache[] = $kingdomId;

        return $cache;
    }

    protected function updateCurrentPopulation() {
        $building = $this->kingdom->buildings->where('is_farm', true)->first();
        $morale   = $this->kingdom->current_morale;

        if ($building->current_durability === 0 || $morale === 0) {
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
            return $this->updateTreasury(100);
        }

        return $this->updateTreasury(50);
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
}
