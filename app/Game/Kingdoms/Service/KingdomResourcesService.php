<?php

namespace App\Game\Kingdoms\Service;

use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\Kingdom;
use App\Flare\Transformers\KingdomTransformer;
use Facades\App\Flare\Values\UserOnlineValue;
use App\Game\Kingdoms\Events\UpdateKingdom;

class KingdomResourcesService {

    private $kingdom;

    private $manager;

    private $kingdomTransformer;

    public function __construct(Manager $manager, KingdomTransformer $kingdomTransformer) {
        $this->manager            = $manager;
        $this->kingdomTransformer = $kingdomTransformer;
    }

    public function setKingdom(Kingdom $kingdom): KingdomResourcesService {
        $this->kingdom = $kingdom;

        return $this;
    }

    public function updateKingdom() {
        $this->updateCurrentPopulation();
        $this->increaseCurrentResource();
        $this->increaseOrDecreaseMorale();

        $user  = $this->kingdom->character->user;
        $kingdom = $this->kingdom;

        if (UserOnlineValue::isOnline($user)) {
            $kingdom = new Item($kingdom, $this->kingdomTransformer);
            $kingdom = $this->manager->createData($kingdom)->toArray();

            event(new UpdateKingdom($user, $kingdom));
            event(new ServerMessageEvent($user, 'kingdom-resources-update', $this->kingdom->name . 'Has updated it\'s resources at Location (x/y): ' . $this->kingdom->x_position . '/' . $this->kingdom->y_position));
        }
    }

    protected function updateCurrentPopulation() {
        $building = $this->kingdom->buildings->where('is_farm', true)->first();

        if ($building->current_durability === 0) {
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

            if ($building->current_durability === 0) {
                continue;
            }

            if (!is_null($building)) {
                $newCurrent = $this->kingdom->{'current_' . $resource} + $building->{'increase_in_'.$resource};

                if ($newCurrent > $this->kingdom->{'max_' . $resource}) {
                    $newCurrent = $this->kingdom->{'max_' . $resource};
                }

                $this->kingdom->{'current_' . $resource} = $newCurrent;

                $this->kingdom->save();
            }
        }

        $this->kingdom = $this->kingdom->refresh();
    }

    protected function increaseOrDecreaseMorale() {
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

        if ($totalDecrease < $totalIncrease) {
            $totalIncrease -= $totalDecrease;
            $totalDecrease = 0;

            return $this->addMorale($totalIncrease);
        } else if ($totalIncrease < $totalDecrease) {
            $totalDecrease -= $totalIncrease;
            $totalIncrease = 0;

            return $this->reduceMorale($totalDecrease);
        }
        
        return $this->adjustMorale($totalIncrease, $totalDecrease);
    }

    private function addMorale(float $toAdd) {
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

    private function reduceMorale(float $toSub) {
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

    private function adjustMorale(float $toAdd, float $toSub) {
        $current = $this->kingdom->current_morale;

        $newTotal = ($current + $toAdd) - $toSub;

        $this->kingdom->update([
            'current_morale' => $newTotal,
        ]);

        $this->kingdom = $this->kingdom->refresh();
    }
}