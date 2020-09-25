<?php

namespace App\Admin\Controllers;

use App\Flare\Models\GameMap;
use App\Flare\Cache\CoordinatesCache;
use App\Flare\Models\Adventure;
use App\Flare\Models\Item;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Flare\Models\Location;

class LocationsController extends Controller {

    public function index() {
        return view('admin.locations.locations', [
            'locations' => Location::all(),
        ]);
    }

    public function show(Location $location) {
        return view('admin.locations.location', [
            'location' => $location,
        ]);
    }

    public function create() {
        return view('admin.locations.manage', [
            'location'    => null,
        ]);
    }

    public function edit(Location $location) {
        return view('admin.locations.manage', [
            'location'    => $location,
        ]);
    }
}
