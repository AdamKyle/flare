<?php

namespace App\Game\Core\Controllers;

use App\Flare\Models\Location;
use App\Flare\Values\LocationEffectValue;
use App\Http\Controllers\Controller;

class LocationsController extends Controller {

    public function show(Location $location) {
        $increasesEnemyStrengthBy = null;
        $increasesDropChanceBy    = 0.0;

        if (!is_null($location->enemy_strength_type)) {
            $increasesEnemyStrengthBy = LocationEffectValue::getIncreaseName($location->enemy_strength_type);
            $increasesDropChanceBy    = (new LocationEffectValue($location->enemy_strength_type))->fetchDropRate();
        }

        return view('admin.locations.location', [
            'location'                 => $location,
            'increasesEnemyStrengthBy' => $increasesEnemyStrengthBy,
            'increasesDropChanceBy'    => $increasesDropChanceBy,
        ]);
    }
}
