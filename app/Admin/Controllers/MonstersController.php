<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Flare\Models\Monster;
use Maatwebsite\Excel\Facades\Excel;
use App\Admin\Exports\Monsters\MonstersExport;

class MonstersController extends Controller {

    public function __construct() {
        $this->middleware('is.admin')->except([
            'show'
        ]);
    }

    public function index() {
        return view('admin.monsters.monsters');
    }

    public function show(Monster $monster) {
        return view('admin.monsters.monster', [
            'monster' => $monster,
        ]);
    }

    public function create() {
        return view('admin.monsters.manage', [
            'monster' => null,
            'editing' => false,
        ]);
    }

    public function edit(Monster $monster) {
        return view('admin.monsters.manage', [
            'monster' => $monster,
            'editing' => true,
        ]);
    }

    public function exportItems() {
        return view('admin.monsters.export');
    }

    public function importItems() {
        return view('admin.monsters.import');
    }

    public function export() {
        $response = Excel::download(new MonstersExport, 'monsters.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        ob_end_clean();

        return $response;
    }

    public function importData(MonstersImportRequest $request) {
        Excel::import(new MonstersImport, $request->items_import);

        return redirect()->back()->with('success', 'imported item data.');
    }

    public function publish(Monster $monster) {
        $monster->update(['published' => true]);

        return redirect()->to(route('monsters.list'))->with('success', 'Monster was published.');
    }
}
