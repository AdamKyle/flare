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

        return view('admin.maps.map', [
            'map'        => $gameMap,
            'itemNeeded' => Item::where('effect', $effects)->first(),
            'mapUrl'     => Storage::disk('maps')->url($gameMap->path),
        ]);
    }

    public function uploadMap() {
        return view('admin.maps.upload');
    }

    public function upload(MapUploadValidation $request) {
        $path = Storage::disk('maps')->putFile($request->name, $request->map);

        GameMap::create([
            'name'          => $request->name,
            'path'          => $path,
            'default'       => $request->default === 'yes' ? true : false,
            'kingdom_color' => $request->kingdom_color,
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
