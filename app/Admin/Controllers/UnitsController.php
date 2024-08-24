<?php

namespace App\Admin\Controllers;

use App\Admin\Requests\KingdomUnitManagementRequest;
use App\Flare\Models\GameBuildingUnit;
use App\Flare\Models\GameUnit;
use App\Http\Controllers\Controller;

class UnitsController extends Controller
{
    public function index()
    {
        return view('admin.kingdoms.units.units');
    }

    public function create()
    {
        return view('admin.kingdoms.units.manage', [
            'unit' => null,
        ]);
    }

    public function show(GameUnit $gameUnit)
    {
        $belongsToKingdomBuilding = GameBuildingUnit::where('game_unit_id', $gameUnit->id)->first();

        if (! is_null($belongsToKingdomBuilding)) {
            $belongsToKingdomBuilding = $belongsToKingdomBuilding->gameBuilding;
        }

        return view('admin.kingdoms.units.unit', [
            'unit' => $gameUnit,
            'building' => $belongsToKingdomBuilding,
            'requiredLevel' => GameBuildingUnit::where('game_building_id', $belongsToKingdomBuilding->id)
                ->where('game_unit_id', $gameUnit->id)
                ->first()->required_level,
        ]);
    }

    public function edit(GameUnit $gameUnit)
    {
        return view('admin.kingdoms.units.manage', [
            'unit' => $gameUnit,
        ]);
    }

    public function store(KingdomUnitManagementRequest $request)
    {
        GameUnit::updateOrCreate([
            'id' => $request->id,
        ], $request->all());

        return redirect()->route('units.list')->with('success', 'Unit: '.$request->name.' saved.');
    }
}
