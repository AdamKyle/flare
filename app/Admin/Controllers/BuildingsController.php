<?php

namespace App\Admin\Controllers;

use App\Flare\Models\GameBuilding;
use App\Http\Controllers\Controller;

class BuildingsController extends Controller {

    public function index() {
        return view('admin.kingdoms.buildings.buildings');
    }

    public function create() {
        return view ('admin.kingdoms.buildings.manage', [
            'building' => null,
            'editing'  => false,
        ]);
    }

    public function show(GameBuilding $building) {
        return view('admin.kingdoms.buildings.building', [
            'building' => $building
        ]);
    }

    public function edit(GameBuilding $building) {
        return view ('admin.kingdoms.buildings.manage', [
            'building' => $building,
            'editing'  => true,
        ]);
    }
    
}
