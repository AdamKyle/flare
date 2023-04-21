<?php

namespace App\Admin\Controllers;

use App\Admin\Services\LocationService;
use App\Flare\Values\LocationEffectValue;
use App\Flare\Values\LocationType;
use App\Http\Controllers\Controller;
use App\Flare\Models\Location;
use Illuminate\Http\Request;

class LocationsController extends Controller {

    private LocationService $locationService;

    public function __construct(LocationService $locationService) {
        $this->locationService = $locationService;
    }

    public function index() {
        return view('admin.locations.locations', [
            'locations' => Location::all(),
        ]);
    }

    public function create() {
        return view('admin.locations.manage', $this->locationService->getViewVariables());
    }

    public function edit(Location $location) {
        return view('admin.locations.manage', $this->locationService->getViewVariables($location));
    }

    public function store(Request $request) {
        Location::updateOrCreate(['id' => $request->id], $request->all());

        return response()->redirectToRoute('locations.list')->with('success', 'Saved Location Details for: ' . $request->name);
    }

    public function show(Location $location) {

        $increasesEnemyStrengthBy = null;
        $locationType             = null;
        $increasesDropChanceBy    = 0.0;

        if (!is_null($location->enemy_strength_type)) {
            $increasesEnemyStrengthBy = LocationEffectValue::getIncreaseName($location->enemy_strength_type);
            $increasesDropChanceBy    = (new LocationEffectValue($location->enemy_strength_type))->fetchDropRate();
        }

        if (!is_null($location->type)) {
            $locationType = (new LocationType($location->type));
        }

        return view('admin.locations.location', [
            'location'                 => $location,
            'increasesEnemyStrengthBy' => $increasesEnemyStrengthBy,
            'increasesDropChanceBy'    => $increasesDropChanceBy,
            'locationType'             => $locationType,
        ]);
    }
}
