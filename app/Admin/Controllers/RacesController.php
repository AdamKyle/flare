<?php

namespace App\Admin\Controllers;

use Illuminate\Http\Request;
use App\Flare\Models\GameRace;
use App\Http\Controllers\Controller;

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
}
