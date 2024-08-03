<?php

namespace App\Admin\Controllers;

use App\Admin\Exports\Races\RacesExport;
use App\Admin\Import\Races\RacesImport;
use App\Flare\Models\GameRace;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

class RacesController extends Controller
{
    public function index()
    {
        return view('admin.races.list');
    }

    public function show(GameRace $race)
    {
        return view('admin.races.race', [
            'race' => $race,
        ]);
    }

    public function create()
    {
        return view('admin.races.manage', [
            'race' => null,
        ]);
    }

    public function edit(GameRace $race)
    {
        return view('admin.races.manage', [
            'race' => $race,
        ]);
    }

    public function store(Request $request)
    {
        $race = GameRace::updateOrCreate(['id' => $request->id], $request->all());

        return response()->redirectToRoute('races.race', ['race' => $race->id])->with('Success', 'Race has been saved.');
    }

    public function exportRaces()
    {
        return view('admin.races.export');
    }

    public function importRaces()
    {
        return view('admin.races.import');
    }

    public function export()
    {
        $response = Excel::download(new RacesExport, 'game_races.xlsx', ExcelWriter::XLSX);
        ob_end_clean();

        return $response;
    }

    public function import(Request $request)
    {
        Excel::import(new RacesImport, $request->races_import);

        return redirect()->back()->with('success', 'imported race data.');
    }
}
