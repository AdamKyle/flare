<?php

namespace App\Admin\Controllers;

use App\Admin\Exports\Affixes\AffixesExport;
use App\Admin\Import\Affixes\AffixesImport;
use App\Admin\Requests\AffixesImport as AffixesImportRequest;
use App\Admin\Requests\AffixManagementRequest;
use App\Admin\Services\ItemAffixService;
use App\Flare\Models\ItemAffix;
use App\Flare\Values\ItemAffixType;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class AffixesController extends Controller
{
    private $itemAffixService;

    public function __construct(ItemAffixService $itemAffixService)
    {
        $this->itemAffixService = $itemAffixService;
    }

    public function index()
    {
        return view('admin.affixes.affixes');
    }

    public function create()
    {

        return view('admin.affixes.manage', array_merge([
            'itemAffix' => null,
            'affixTypes' => ItemAffixType::$dropDownValues,
        ], $this->itemAffixService->getFormData()));
    }

    public function show(ItemAffix $affix)
    {

        return view('admin.affixes.affix', [
            'itemAffix' => $affix,
        ]);
    }

    public function edit(ItemAffix $affix)
    {
        return view('admin.affixes.manage', array_merge([
            'itemAffix' => $affix,
            'affixTypes' => ItemAffixType::$dropDownValues,
        ], $this->itemAffixService->getFormData()));
    }

    public function store(AffixManagementRequest $request)
    {
        $data = $this->itemAffixService->cleanRequestData($request->all());

        $affix = ItemAffix::updateOrCreate(['id' => $data['id']], $data);

        $message = 'Created: '.$affix['name'];

        if ($affix['id'] !== 0) {
            $message = 'Updated: '.$affix['name'];
        }

        return response()->redirectToRoute('affixes.affix', ['affix' => $affix->id])->with('success', $message);
    }

    public function delete(Request $request, ItemAffixService $itemAffixService, ItemAffix $affix)
    {
        $name = $affix->name;

        $itemAffixService->deleteAffix($affix);

        return redirect()->back()->with('success', $name.' was deleted successfully.');
    }

    public function exportItems()
    {
        return view('admin.affixes.export', [
            'types' => ItemAffixType::$dropDownValues,
        ]);
    }

    public function importItems()
    {
        return view('admin.affixes.import');
    }

    /**
     * @codeCoverageIgnore
     */
    public function export(Request $request)
    {

        if (! $request->has('export_type')) {
            return redirect()->back()->with('error', 'No export type selected.');
        }

        $type = $request->export_type;

        $fileName = Str::snake(preg_replace('/[^a-zA-Z0-9\s]/', '', ItemAffixType::$dropDownValues[$type]));

        $response = Excel::download(new AffixesExport($type), $fileName.'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        ob_end_clean();

        return $response;
    }

    /**
     * @codeCoverageIgnore
     */
    public function importData(AffixesImportRequest $request)
    {
        Excel::import(new AffixesImport, $request->affixes_import);

        return redirect()->back()->with('success', 'imported affix data.');
    }
}
