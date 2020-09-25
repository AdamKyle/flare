<?php

namespace App\Admin\Controllers;

use App\Admin\Requests\AdventureValidation;
use App\Flare\Models\GameMap;
use App\Flare\Cache\CoordinatesCache;
use App\Flare\Models\Adventure;
use App\Flare\Models\Item;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;

class AdventuresController extends Controller {

    public function __construct() {
        //
    }

    public function index() {
        return view('admin.adventures.adventures', [
            'adventures' => Adventure::all(),
        ]);
    }

    public function show(Adventure $adventure) {
        return view('admin.adventures.adventure', [
            'adventure' => $adventure,
        ]);
    }

    public function create() {
        return view('admin.adventures.manage', [
            'adventure' => null,
            'locations' => Location::all()->pluck('name', 'id')->toArray(),
            'items'     => Item::where('type', 'quest')->pluck('name', 'id')->toArray(),
            'monsters'  => Monster::all()->pluck('name', 'id')->toArray(),
        ]);
    }

    public function edit(Adventure $adventure) {
        return view('admin.adventures.manage', [
            'adventure' => $adventure,
            'locations' => Location::all()->pluck('name', 'id')->toArray(),
            'items'     => Item::where('type', 'quest')->pluck('name', 'id')->toArray(),
            'monsters'  => Monster::all()->pluck('name', 'id')->toArray(),
        ]);
    }

    public function update(AdventureValidation $request, Adventure $adventure) {
        $requestForModel = $request->except(['_token', 'location_ids', 'monster_ids']);

        $adventure->update($requestForModel);

        $adventure->locations()->sync($request->location_ids);
        $adventure->monsters()->sync($request->monster_ids);
        
        return redirect()->route('adventures.adventure', [
            'adventure' => $adventure->id
        ])->with('success', $adventure->name . ' updated!');
    }

    public function store(AdventureValidation $request) {
        $requestForModel = $request->except(['_token', 'location_ids', 'monster_ids']);

        $adventure = Adventure::create($requestForModel);

        $adventure->locations()->attach($request->location_ids);

        $adventure->monsters()->attach($request->monster_ids);
        
        return redirect()->route('adventures.adventure', [
            'adventure' => $adventure->id
        ])->with('success', $adventure->name . ' created!');
    }
}
