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
use App\Admin\Exports\Raids\RaidExport;
use App\Admin\Import\Raids\RaidsImport;
use App\Admin\Requests\MonstersImport as MonstersImportRequest;
use App\Admin\Requests\RaidImportRequest;
use App\Flare\Values\ItemSpecialtyType;

class RaidsController extends Controller {

    public function index() {
        return view('admin.raids.list', [
            'gameMapNames' => GameMap::all()->pluck('name')->toArray(),
        ]);
    }

    public function show(Raid $raid) {
        $monsters = Monster::whereIn('id', $raid->raid_monster_ids)->select('id', 'name')->get()->toArray();

        return view('information.raids.raid', [
            'raid'         => $raid,
            'raidMonsters' => array_chunk($monsters, ceil(count($monsters) / 2))
        ]);
    }

    public function create() {
        return view('admin.raids.manage', [
            'raid'        => null,
            'monsters'    => Monster::where('is_raid_monster', true)->get(),
            'locations'   => Location::all(),
            'raidBosses'  => Monster::where('is_raid_boss', true)->get(),
            'itemTypes'   => [
                ItemSpecialtyType::PIRATE_LORD_LEATHER,
            ]
        ]);
    }

    public function edit(Raid $raid) {
        return view('admin.raids.manage', [
            'raid'       => $raid,
            'monsters'   => Monster::where('is_raid_monster', true)->get(),
            'locations'  => Location::all(),
            'raidBosses' => Monster::where('is_raid_boss', true)->get(),
            'itemTypes'  => [
                ItemSpecialtyType::PIRATE_LORD_LEATHER,
            ]
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
    public function export() {

        $fileName = 'raids.xlsx';

        $response = Excel::download(new RaidExport, $fileName, \Maatwebsite\Excel\Excel::XLSX);
        ob_end_clean();

        return $response;
    }

    /**
     * @codeCoverageIgnore
     */
    public function import(RaidImportRequest $request) {
        Excel::import(new RaidsImport, $request->raids);

        return redirect()->back()->with('success', 'imported raid data.');
    }

    public function store(Request $request) {

        $raid = Raid::find($request->id);

        if (is_null($raid)) {
            $raid = Raid::create($request->all());
        } else {
            $raid->update($request->all());
        }

        $raid = $raid->refresh();

        return response()->redirectToRoute('admin.raids.show', ['raid' => $raid->id])->with('success', 'Saved raid: ' . $raid->name);
    }
}
