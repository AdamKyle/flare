<?php

namespace App\Admin\Controllers;

use App\Flare\Models\GameKingdomBuilding;
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

    public function show(GameKingdomBuilding $building) {
        return view('admin.kingdoms.buildings.building', [
            'building' => $building
        ]);
    }

    public function edit(GameKingdomBuilding $building) {
        return view ('admin.kingdoms.buildings.manage', [
            'building' => $building,
            'editing'  => true,
        ]);
    }
    
}
