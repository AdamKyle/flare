<?php

namespace App\Admin\Controllers;

use App\Admin\Import\Affixes\GuideQuests;
use App\Admin\Requests\AffixManagementRequest;
use App\Flare\Models\GameSkill;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Admin\Exports\Affixes\AffixesExport;
use App\Admin\Services\ItemAffixService;
use App\Flare\Models\ItemAffix;
use App\Admin\Requests\AffixesImport as AffixesImportRequest;

class AffixesController extends Controller {

    private $itemAffixService;

    public function __construct(ItemAffixService $itemAffixService) {
        $this->itemAffixService = $itemAffixService;
    }

    public function index() {
        return view('admin.affixes.affixes', [
            'affixes' => ItemAffix::all(),
        ]);
    }

    public function create() {

        return view('admin.affixes.manage', array_merge([
            'itemAffix' => null,
        ], $this->itemAffixService->getFormData()));
    }

    public function show(ItemAffix $affix) {

        return view('admin.affixes.affix', [
            'itemAffix' => $affix,
        ]);
    }

    public function edit(ItemAffix $affix) {
        return view('admin.affixes.manage', array_merge([
            'itemAffix' => $affix,
        ], $this->itemAffixService->getFormData()));
    }

    public function store(AffixManagementRequest $request) {
        $data = $this->itemAffixService->cleanRequestData($request->all());

        $affix = ItemAffix::updateOrCreate(['id' => $data['id']], $data);

        $message = 'Created: ' . $affix['name'];

        if ($affix['id'] !== 0) {
            $message = 'Updated: ' . $affix['name'];
        }

        return response()->redirectToRoute('affixes.affix', ['affix' => $affix->id])->with('success', $message);
    }

    public function delete(Request $request, ItemAffixService $itemAffixService, ItemAffix $affix) {
        $name = $affix->name;

        $itemAffixService->deleteAffix($affix);

        return redirect()->back()->with('success', $name . ' was deleted successfully.');
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
    public function export(Request $request) {

        if (!$request->has('export_type')) {
            return redirect()->back()->with('error', 'No export type selected.');
        }

        $type = $request->export_type;

        $fileName = ucFirst($type);

        if (preg_match('/_/', $type)) {
           $fileName = ucFirst(str_replace('_', ' ', $type));
        }

        $response = Excel::download(new AffixesExport($type), $fileName . ' (Affixes).xlsx', \Maatwebsite\Excel\Excel::XLSX);
        ob_end_clean();

        return $response;
    }

    /**
     * @codeCoverageIgnore
     */
    public function importData(AffixesImportRequest $request) {
        Excel::import(new GuideQuests, $request->affixes_import);

        return redirect()->back()->with('success', 'imported affix data.');
    }
}
