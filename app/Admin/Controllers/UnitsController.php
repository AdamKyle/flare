<?php

namespace App\Admin\Controllers;

use App\Flare\Models\GameKingdomBuildingUnit;
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
        $belongsToKingdomBuilding = GameKingdomBuildingUnit::where('game_unit_id', $gameUnit->id)->first();

        if (!is_null($belongsToKingdomBuilding)) {
            $belongsToKingdomBuilding = $belongsToKingdomBuilding->gameKingdomBuilding;
        }

        $weakAgainst       = GameUnit::find($gameUnit->weak_against_unit_id);

        return view('admin.kingdoms.units.unit', [
            'unit'        => $gameUnit,
            'building'    => $belongsToKingdomBuilding,
            'weakAgainst' => $weakAgainst,
        ]);
    }

    public function edit(GameUnit $gameUnit) {
        return view ('admin.kingdoms.units.manage', [
            'unit'    => $gameUnit,
            'editing' => true,
        ]);
    }
    
}
