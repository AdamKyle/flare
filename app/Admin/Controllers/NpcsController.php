<?php

namespace App\Admin\Controllers;

use App\Admin\Exports\Npcs\NpcsExport;
use App\Admin\Import\Npcs\NpcsImport;
use App\Admin\Requests\NpcsImportRequest;
use App\Admin\Requests\StoreNpcRequest;
use App\Flare\Models\GameMap;
use App\Flare\Models\Npc;
use App\Flare\Values\NpcTypes;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class NpcsController extends Controller
{
    public function index()
    {
        return view('admin.npcs.index');
    }

    public function show(Npc $npc)
    {

        $npc->game_map_name = $npc->gameMap->name;
        $npc->type_name = (new NpcTypes($npc->type))->getNamedValue();

        return view('admin.npcs.show', [
            'npc' => $npc,
        ]);
    }

    public function create()
    {
        return view('admin.npcs.manage', [
            'npc' => null,
            'gameMaps' => GameMap::pluck('name', 'id')->toArray(),
            'types' => NpcTypes::getNamedValues(),
        ]);
    }

    public function edit(Npc $npc)
    {
        return view('admin.npcs.manage', [
            'npc' => $npc,
            'gameMaps' => GameMap::pluck('name', 'id')->toArray(),
            'types' => NpcTypes::getNamedValues(),
        ]);
    }

    public function exportNpcs()
    {
        return view('admin.npcs.export');
    }

    public function importNpcs()
    {
        return view('admin.npcs.import');
    }

    public function store(StoreNpcRequest $request)
    {
        $params = [...$request->all(), ...['name' => str_replace(' ', '', $request->real_name)]];
        $npc = Npc::updateOrCreate(['id' => $request->npc_id], $params);

        return response()->redirectToRoute('npcs.show', ['npc' => $npc->id])->with('success', 'Saved: '.$npc->real_name);
    }

    /**
     * @codeCoverageIgnore
     */
    public function export()
    {
        $response = Excel::download(new NpcsExport, 'npcs.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        ob_end_clean();

        return $response;
    }

    /**
     * @codeCoverageIgnore
     */
    public function import(NpcsImportRequest $request)
    {
        Excel::import(new NpcsImport, $request->npcs_import);

        return redirect()->back()->with('success', 'imported npc data.');
    }
}
