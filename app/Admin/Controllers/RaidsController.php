<?php

namespace App\Admin\Controllers;

use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Flare\Models\Raid;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Flare\Models\Monster;
use App\Admin\Import\Monsters\MonstersImport;
use App\Admin\Exports\Monsters\MonstersExport;
use App\Admin\Requests\MonstersImport as MonstersImportRequest;

class RaidsController extends Controller {

    public function index() {
        return view('admin.raids.list', [
            'gameMapNames' => GameMap::all()->pluck('name')->toArray(),
        ]);
    }

    public function show(Raid $raid) {
        return view('admin.raids.raid', [
            'raid' => $raid
        ]);
    }

    public function create() {
        return view('admin.raids.manage', [
            'raid'        => null,
            'monsters'    => Monster::where('is_raid_monster', true)->get(),
            'locations'   => Location::all(),
            'raidBosses'  => Monster::where('is_raid_boss', true)->get(),
        ]);
    }

    public function edit(Raid $raid) {
        return view('admin.raids.manage', [
            'raid'        => $raid,
            'monsters'    => Monster::where('is_raid_monster', true)->get(),
            'locations'   => Location::all(),
            'raid_bosses' => Monster::where('is_raid_boss', true)->get(),
        ]);
    }

    public function exportRaids() {
        return view('admin.raids.export');
    }

    public function importRaids() {
        return view('admin.raids.import');
    }

    /**
     * @codeCoverageIgnore
     */
    public function export(Request $request) {

        $fileName = 'raids.xlsx';

        $response = Excel::download(new MonstersExport($request->monster_type), $fileName, \Maatwebsite\Excel\Excel::XLSX);
        ob_end_clean();

        return $response;
    }

    /**
     * @codeCoverageIgnore
     */
    public function importData(MonstersImportRequest $request) {
        Excel::import(new MonstersImport, $request->monsters_import);

        return redirect()->back()->with('success', 'imported raid data.');
    }

    public function store(Request $request) {
        $raid = Raid::create($request->all());

        return response()->redirectToRoute('admin.raids.show', ['raid' => $raid->id])->with('success', 'Saved raid: ' . $raid->name);
    }
}
