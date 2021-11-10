<?php

namespace App\Admin\Controllers;

use App\Flare\Values\LocationEffectValue;
use App\Http\Controllers\Controller;
use App\Flare\Models\Location;

class LocationsController extends Controller {

    public function index() {
        return view('admin.locations.locations', [
            'locations' => Location::all(),
        ]);
    }

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

    public function create() {
        return view('admin.locations.manage', [
            'location'    => null,
            'editing'     => false,
        ]);
    }

    public function edit(Location $location) {
        return view('admin.locations.manage', [
            'location'    => $location,
            'editing'     => true,
        ]);
    }
}
