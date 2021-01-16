<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Flare\Models\GameUnit;

class UnitsController extends Controller {

    public function index() {
        return view('admin.kingdoms.units.units');
    }

    public function create() {
        return view ('admin.kingdoms.units.manage', [
            'unit'    => null,
            'editing' => false,
        ]);
    }

    public function show(GameUnit $gameUnit) {
        return view('admin.kingdoms.units.unit', [
            'unit' => $gameUnit
        ]);
    }

    public function edit(GameUnit $gameUnit) {
        return view ('admin.kingdoms.units.manage', [
            'unit'    => $gameUnit,
            'editing' => true,
        ]);
    }
    
}
