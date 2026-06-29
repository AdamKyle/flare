<?php

namespace App\Admin\Controllers;

use App\Admin\Exports\LocationTemplates\LocationTemplatesExport;
use App\Admin\Import\LocationTemplates\LocationTemplatesImport;
use App\Admin\Requests\LocationTemplateManagementRequest;
use App\Admin\Requests\LocationTemplatesImportRequest;
use App\Flare\Models\LocationTemplate;
use App\Flare\Values\LocationTemplateType;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LocationTemplatesController extends Controller
{
    public function index(): View
    {
        return view('admin.location-templates.index', [
            'locationTemplates' => LocationTemplate::orderBy('type')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.location-templates.manage', [
            'locationTemplate' => null,
            'templateTypes' => LocationTemplateType::getNamedValues(),
        ]);
    }

    public function edit(LocationTemplate $locationTemplate): View
    {
        return view('admin.location-templates.manage', [
            'locationTemplate' => $locationTemplate,
            'templateTypes' => LocationTemplateType::getNamedValues(),
        ]);
    }

    public function show(LocationTemplate $locationTemplate): View
    {
        return view('admin.location-templates.show', [
            'locationTemplate' => $locationTemplate,
        ]);
    }

    public function store(LocationTemplateManagementRequest $request): RedirectResponse
    {
        $validatedData = $request->validated();
        $validatedData['can_players_enter'] = $request->boolean('can_players_enter', true);

        $locationTemplate = null;

        if ($request->integer('id') !== 0) {
            $locationTemplate = LocationTemplate::find($request->integer('id'));
        }

        if (is_null($locationTemplate)) {
            $locationTemplate = LocationTemplate::create($validatedData);
            $message = 'Created '.$locationTemplate->name;
        } else {
            $locationTemplate->update($validatedData);
            $message = 'Updated '.$locationTemplate->name;
        }

        return response()
            ->redirectToRoute('admin.location-templates.show', ['locationTemplate' => $locationTemplate])
            ->with('success', $message);
    }

    public function delete(LocationTemplate $locationTemplate): RedirectResponse
    {
        $locationTemplate->delete();

        return response()
            ->redirectToRoute('admin.location-templates.list')
            ->with('success', 'Deleted location template.');
    }

    public function exportLocationTemplates(): View
    {
        return view('admin.location-templates.export');
    }

    public function importLocationTemplates(): View
    {
        return view('admin.location-templates.import');
    }

    public function export(): BinaryFileResponse
    {
        return Excel::download(new LocationTemplatesExport, 'location-templates.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }

    public function importData(LocationTemplatesImportRequest $request): RedirectResponse
    {
        Excel::import(new LocationTemplatesImport, $request->file('location_templates_import'));

        return redirect()->back()->with('success', 'Imported location template data.');
    }
}
