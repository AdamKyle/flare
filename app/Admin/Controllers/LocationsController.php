<?php

namespace App\Admin\Controllers;

use App\Admin\Exports\Locations\LocationsExport;
use App\Admin\Import\Locations\LocationsImport;
use App\Admin\Requests\LocationsImportRequest;
use App\Admin\Services\LocationService;
use App\Flare\Models\Location;
use App\Flare\Models\Quest;
use App\Flare\Values\LocationEffectValue;
use App\Flare\Values\LocationType;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LocationsController extends Controller
{
    private LocationService $locationService;

    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }

    public function index()
    {
        return view('admin.locations.locations', [
            'locations' => Location::all(),
        ]);
    }

    public function create()
    {
        return view('admin.locations.manage', $this->locationService->getViewVariables());
    }

    public function edit(Location $location)
    {
        return view('admin.locations.manage', $this->locationService->getViewVariables($location));
    }

    public function store(Request $request)
    {
        Location::updateOrCreate(['id' => $request->id], $request->all());

        return response()->redirectToRoute('locations.list')->with('success', 'Saved Location Details for: ' . $request->name);
    }

    public function show(Location $location)
    {

        $increasesEnemyStrengthBy = null;
        $locationType = null;
        $increasesDropChanceBy = 0.0;
        $usedInQuest = null;

        if (! is_null($location->enemy_strength_type)) {
            $increasesEnemyStrengthBy = LocationEffectValue::getIncreaseName($location->enemy_strength_type);
            $increasesDropChanceBy = (new LocationEffectValue($location->enemy_strength_type))->fetchDropRate();
        }

        if (! is_null($location->type)) {
            $locationType = (new LocationType($location->type));
        }

        if (! is_null($location->questRewardItem)) {

            $usedInQuest = Quest::where('item_id', $location->quest_reward_item_id)->first();

            if (is_null($usedInQuest)) {
                $usedInQuest = Quest::where('secondary_required_item', $location->quest_reward_item_id)->first();
            }
        }

        return view('information.locations.location', [
            'location' => $location,
            'increasesEnemyStrengthBy' => $increasesEnemyStrengthBy,
            'increasesDropChanceBy' => $increasesDropChanceBy,
            'locationType' => $locationType,
            'usedInQuest' => $usedInQuest,
        ]);
    }

    public function exportLocations()
    {
        return view('admin.locations.export');
    }

    public function importLocations()
    {
        return view('admin.locations.import');
    }

    /**
     * @codeCoverageIgnore
     */
    public function export()
    {
        $response = Excel::download(new LocationsExport, 'locations.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        ob_end_clean();

        return $response;
    }

    /**
     * @codeCoverageIgnore
     */
    public function importData(LocationsImportRequest $request)
    {
        Excel::import(new LocationsImport, $request->locations_import);

        return redirect()->back()->with('success', 'imported location data.');
    }
}
