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
            'monster' => Monster::first(),
        ]);
    }

    public function edit(Monster $monster) {
        return view('admin.monsters.manage', [
            'monster' => $monster,
        ]);
    }
}
