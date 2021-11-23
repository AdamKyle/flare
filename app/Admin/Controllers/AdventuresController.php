<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Admin\Requests\AdventureValidation;
use App\Flare\Models\Adventure;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use Cache;
use Illuminate\Http\Request;

class AdventuresController extends Controller {

    public function index() {
        return view('admin.adventures.adventures');
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
            'monsters'  => Monster::where('published', true)->where('is_celestial_entity', false)->orderBy('game_map_id', 'asc')->orderBy('max_level', 'asc')->pluck('name', 'id')->toArray(),
        ]);
    }

    public function edit(Adventure $adventure) {
        return view('admin.adventures.manage', [
            'adventure' => $adventure,
            'locations' => Location::all()->pluck('name', 'id')->toArray(),
            'items'     => Item::where('type', 'quest')->pluck('name', 'id')->toArray(),
            'monsters'  => Monster::where('published', true)->where('is_celestial_entity', false)->orderBy('game_map_id', 'asc')->orderBy('max_level', 'asc')->pluck('name', 'id')->toArray(),
        ]);
    }

    public function floorDescriptions(Adventure $adventure) {
        return view('admin.adventures.floor_descriptions', [
            'adventure' => $adventure,
        ]);
    }

    public function update(AdventureValidation $request, Adventure $adventure) {
        $requestForModel = $request->except(['_token', 'location_ids', 'monster_ids']);

        $adventure->update($requestForModel);

        $adventure->locations()->sync($request->location_ids);
        $adventure->monsters()->sync($request->monster_ids);

        return redirect()->route('adventure.floor_descriptions', [
            'adventure' => $adventure->id
        ])->with('success', $adventure->name . ' updated! Please update your floor descriptions.');
    }

    public function store(AdventureValidation $request) {
        $requestForModel = $request->except(['_token', 'location_ids', 'monster_ids']);

        $adventure = Adventure::create(array_merge($requestForModel, ['published' => false]));

        $adventure->monsters()->attach($request->monster_ids);

        return redirect()->route('adventure.floor_descriptions', [
            'adventure' => $adventure->id
        ])->with('success', $adventure->name . ' created! Please create some floor descriptions for each floor.');
    }

    public function saveFloorDescriptions(Request $request, Adventure $adventure) {
        $floorDescriptions = $request->all();

        unset($floorDescriptions['_token']);

        if (!$this->validateFloorDescriptions($adventure->levels, $floorDescriptions)) {
            return redirect()->back()->withInput($request->input())->with('error', 'Missing floor descriptions. Each floor needs a description.');
        }

        if ($adventure->floorDescriptions->isNotEmpty()) {
            $adventure->floorDescriptions()->delete();
        }

        foreach ($floorDescriptions as $key => $description) {
            $adventure->floorDescriptions()->create([
                'adventure_id' => $adventure->id,
                'description'  => $description,
            ]);
        }

        return redirect()->to(route('adventures.adventure', ['adventure' => $adventure]))->with('success', 'Saved floor descriptions.');
    }

    protected function validateFloorDescriptions(int $levels, array $request): bool {
        for ($i = 1; $i <= $levels; $i++) {
            if (is_null($request['level-' . $i])) {
                return false;
            }
        }

        return true;
    }

    public function publish(Adventure $adventure) {
        $adventure->update(['published' => true]);

        return redirect()->to(route('adventures.adventure', [
            'adventure' => $adventure->refresh()
        ]))->with('success', 'Adventure published.');
    }
}
