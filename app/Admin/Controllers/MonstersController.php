<?php

namespace App\Admin\Controllers;

use App\Flare\Models\Adventure;
use App\Flare\Models\Item;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;

class MonstersController extends Controller {

    public function __construct() {
        //
    }

    public function index() {
        return view('admin.monsters.monsters', [
            'adventures' => Monster::all(),
        ]);
    }

    public function show(Adventure $adventure) {
        return view('admin.monsters.monster', [
            'adventure' => $adventure,
        ]);
    }

    public function create() {
        return view('admin.monsters.manage', [
            'adventure' => null,
            'locations' => Location::all()->pluck('name', 'id')->toArray(),
            'items'     => Item::where('type', 'quest')->pluck('name', 'id')->toArray(),
            'monsters'  => Monster::all()->pluck('name', 'id')->toArray(),
        ]);
    }

    public function edit(Monster $monster) {
        return view('admin.monsters.manage', [
            'monster' => $monster,
        ]);
    }
}
