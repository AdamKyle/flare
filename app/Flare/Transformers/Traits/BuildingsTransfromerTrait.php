<?php

namespace App\Flare\Transformers\Traits;

use App\Flare\Models\Kingdom;

trait BuildingsTransfromerTrait {

    public function fetchBuildings(Kingdom $kingdom) {
        return $kingdom->buildings->transform(function($building) {
            $building->name        = $building->name;
            $building->description = $building->description;

            return $building;
        })->all();
    }
}