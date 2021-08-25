<?php

namespace App\Admin\Controllers;

use Maatwebsite\Excel\Facades\Excel;
use App\Admin\Requests\NpcsImportRequest;
use App\Http\Controllers\Controller;
use App\Flare\Models\Npc;
use App\Flare\Values\NpcTypes;
use App\Admin\Exports\Npcs\NpcsExport;
use App\Admin\Import\Npcs\NpcsImport;

class NpcsController extends Controller {

    public function index() {
        return view('admin.npcs.index');
    }

    public function show(Npc $npc) {

        $npc->game_map_name = $npc->gameMap->name;
        $npc->type          = (new NpcTypes($npc->type))->getNamedValue();

        return view('admin.npcs.show', [
            'npc' => $npc,
        ]);
    }

    public function create() {
        return view('admin.npcs.manage', [
            'npc'     => null,
            'editing' => false,
        ]);
    }

    public function edit(Npc $npc) {
        return view('admin.npcs.manage', [
            'npc'     => $npc,
            'editing' => true,
        ]);
    }

    public function exportNpcs() {
        return view('admin.npcs.export');
    }

    public function importNpcs() {
        return view('admin.npcs.import');
    }

    /**
     * @codeCoverageIgnore
     */
    public function export() {
        $response = Excel::download(new NpcsExport(), 'npcs.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        ob_end_clean();

        return $response;
    }

    /**
     * @codeCoverageIgnore
     */
    public function import(NpcsImportRequest $request) {
        Excel::import(new NpcsImport, $request->npcs_import);

        return redirect()->back()->with('success', 'imported npc data.');
    }
}
