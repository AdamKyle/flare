<?php

namespace App\Admin\Controllers;

use App\Admin\Import\Affixes\AffixesImport;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Admin\Exports\Affixes\AffixesExport;
use App\Admin\Services\ItemAffixService;
use App\Flare\Models\ItemAffix;
use App\Admin\Requests\AffixesImport as AffixesImportRequest;

class AffixesController extends Controller {

    public function index() {
        return view('admin.affixes.affixes', [
            'affixes' => ItemAffix::all(),
        ]);
    }

    public function create() {
        return view('admin.affixes.manage', [
            'itemAffix' => null,
            'editing'   => false,
        ]);
    }

    public function show(ItemAffix $affix) {

        return view('admin.affixes.affix', [
            'itemAffix' => $affix,
        ]);
    }

    public function edit(ItemAffix $affix) {
        return view('admin.affixes.manage', [
            'itemAffix' => $affix,
            'editing'   => true,
        ]);
    }

    public function delete(Request $request, ItemAffixService $itemAffixService, ItemAffix $affix) {
        $name = $affix->name;

        $itemAffixService->deleteAffix($affix);

        return redirect()->back()->with('success', $name . ' was deleated successfully.');
    }

    public function exportItems() {
        return view('admin.affixes.export');
    }

    public function importItems() {
        return view('admin.affixes.import');
    }

    /**
     * @codeCoverageIgnore
     */
    public function export() {
        $response = Excel::download(new AffixesExport, 'affixes.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        ob_end_clean();

        return $response;
    }

    /**
     * @codeCoverageIgnore
     */
    public function importData(AffixesImportRequest $request) {
        Excel::import(new AffixesImport, $request->affixes_import);

        return redirect()->back()->with('success', 'imported affix data.');
    }
}
