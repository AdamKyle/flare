<?php

namespace App\Admin\Controllers;

use Storage;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Models\GameMap;
use App\Admin\Requests\MapUploadValidation;
use App\Game\Events\Values\EventType;

class MapsController extends Controller {

    public function index() {
        return view('admin.maps.maps', [
            'maps' => GameMap::all()
        ]);
    }

    public function show(GameMap $gameMap) {
        $effects = match ($gameMap->name) {
            'Labyrinth'    => ItemEffectsValue::LABYRINTH,
            'Dungeons'     => ItemEffectsValue::DUNGEON,
            'Shadow Plane' => ItemEffectsValue::SHADOWPLANE,
            'Hell'         => ItemEffectsValue::HELL,
            'Purgatory'    => ItemEffectsValue::PURGATORY,
            default        => '',
        };

        $walkOnWater = null;

        if ($gameMap->mapType()->isHell()) {
            $walkOnWater = Item::where('effect', ItemEffectsValue::WALK_ON_MAGMA)->first();
        }

        if ($gameMap->mapType()->isDungeons()) {
            $walkOnWater = Item::where('effect', ItemEffectsValue::WALK_ON_DEATH_WATER)->first();
        }

        if ($gameMap->mapType()->isSurface() || $gameMap->mapType()->isLabyrinth()) {
            $walkOnWater = Item::where('effect', ItemEffectsValue::WALK_ON_WATER)->first();
        }

        if ($gameMap->mapType()->isTheIcePlane()) {
            $walkOnWater = Item::where('effect', ItemEffectsValue::WALK_ON_ICE)->first();
        }

        return view('admin.maps.map', [
            'map'         => $gameMap,
            'itemNeeded'  => Item::where('effect', $effects)->first(),
            'walkOnWater' => $walkOnWater,
            'mapUrl'      => Storage::disk('maps')->url($gameMap->path),
        ]);
    }

    public function uploadMap() {
        return view('admin.maps.upload', [
            'mapDetails' => null,
            'eventTypes' => EventType::getOptionsForSelect(),
        ]);
    }

    public function upload(MapUploadValidation $request) {
        $path = Storage::disk('maps')->putFile($request->name, $request->map);

        GameMap::create([
            'name'                   => $request->name,
            'path'                   => $path,
            'default'                => $request->default,
            'kingdom_color'          => $request->kingdom_color,
            'only_during_event_type' => $request->only_during_event,
        ]);

        return redirect()->route('maps')->with('success', $request->name . ' uploaded successfully.');
    }

    public function manageBonuses(GameMap $gameMap) {
        return view('admin.maps.manage-bonuses', ['gameMap' => $gameMap, 'locations' => Location::all()]);
    }

    public function postBonuses(Request $request, GameMap $gameMap) {
        $gameMap->update($request->all());

        return redirect()->route('map', ['gameMap' => $gameMap->id])->with('success', $gameMap->name . ' now has bonuses.');
    }
}
