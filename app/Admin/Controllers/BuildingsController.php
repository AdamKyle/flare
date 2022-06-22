<?php

namespace App\Admin\Controllers;

use App\Admin\Requests\KingdomBuildingManagementRequest;
use App\Admin\Services\UpdateKingdomsService;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameBuildingUnit;
use App\Flare\Models\GameUnit;
use App\Flare\Models\PassiveSkill;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;

class BuildingsController extends Controller {

    /**
     * @var UpdateKingdomsService $updateKingdomService
     */
    private UpdateKingdomsService $updateKingdomService;

    /**
     * @param UpdateKingdomsService $updateKingdomsService
     */
    public function __construct(UpdateKingdomsService $updateKingdomsService) {
        $this->updateKingdomService = $updateKingdomsService;
    }

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
            'building'         => $building,
            'passiveSkills'    => PassiveSkill::all(),
            'unitsForBuilding' => GameBuildingUnit::where('game_building_id', $building->id)->pluck('game_unit_id')->toArray(),
            'recruitableUnits' => GameUnit::all(),
        ]);
    }

    public function store(KingdomBuildingManagementRequest $request) {
        try {
            $data = $this->updateKingdomService->cleanRequestData($request->all());

            $gameBuilding = GameBuilding::updateOrCreate([
                'id' => $request->id,
            ], $data);

            // In case they don't have the building.
            $this->updateKingdomService->assignNewBuildingsToCharacters($gameBuilding);

            $this->updateKingdomService->updateKingdomKingdomBuildings($gameBuilding, $request->units_to_recruit, (int) $request->units_per_level);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', $gameBuilding->name . ' saved!');
    }

}
