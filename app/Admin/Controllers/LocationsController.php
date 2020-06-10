<?php

namespace App\Admin\Controllers;

use App\Admin\Models\GameMap;
use App\Flare\Cache\CoordinatesCache;
use App\Flare\Models\Adventure;
use App\Flare\Models\Item;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Flare\Models\Location;

class LocationsController extends Controller {

    public function __construct() {
        //
    }

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

    public function create(CoordinatesCache $coordinatesCache) {
        return view('admin.locations.manage', [
            'location'    => null,
            'maps'        => GameMap::all()->pluck('name', 'id')->toArray(),
            'coordinates' => $coordinatesCache->getFromCache(),
            'items'       => Item::where('type', 'quest')->pluck('name', 'id')->toArray(),
        ]);
    }

    public function edit(CoordinatesCache $coordinatesCache, Location $location) {
        return view('admin.locations.manage', [
            'location'    => $location,
            'maps'        => GameMap::all()->pluck('name', 'id')->toArray(),
            'coordinates' => $coordinatesCache->getFromCache(),
            'items'       => Item::where('type', 'quest')->pluck('name', 'id')->toArray(),
        ]);
    }

    public function update(Request $request, Location $location) {
        $location->update([
            'name'                 => $request->name,
            'game_map_id'          => (int) $request->map_id,
            'quest_reward_item_id' => (int) $request->quest_item_id,
            'description'          => $request->description,
            'x'                    => (int) $request->x_position,
            'y'                    => (int) $request->y_position,
        ]);
        
        return redirect()->route('locations.location', [
            'location' => $location->id
        ])->with('success', $location->name . ' updated!');
    }

    public function store(Request $request) {
        $location = Location::create([
            'name'                 => $request->name,
            'game_map_id'          => (int) $request->map_id,
            'quest_reward_item_id' => (int) $request->quest_item_id,
            'description'          => $request->description,
            'x'                    => (int) $request->x_position,
            'y'                    => (int) $request->y_position,
        ]);
        
        return redirect()->route('locations.location', [
            'location' => $location->id
        ])->with('success', $location->name . ' created!');
    }
}
