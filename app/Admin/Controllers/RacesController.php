<?php

namespace App\Admin\Controllers;

use App\Flare\Models\GameRace;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RacesController extends Controller {

    public function index() {
        return view('admin.races.list');
    }

    public function show(GameRace $race) {
        return view('admin.races.race', [
            'race' => $race,
        ]);
    }

    public function create() {
        return view('admin.races.manage', [
            'race' => null,
        ]);
    }

    public function edit(GameRace $race) {
        return view('admin.races.manage', [
            'race' => $race,
        ]);
    }

    public function store(Request $request) {
        $race = GameRace::updateOrCreate(['id' => $request->id], $request->all());

        return response()->redirectToRoute('races.race', ['race' => $race->id])->with('Success', 'Race has been saved.');
    }
}
