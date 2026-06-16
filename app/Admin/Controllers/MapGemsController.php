<?php

namespace App\Admin\Controllers;

use App\Admin\Exports\MapGems\MapGemsExport;
use App\Admin\Import\MapGems\MapGemsImport;
use App\Admin\Requests\MapGemParamtersImportRequest;
use App\Admin\Requests\MapGemParamtersManagementRequest;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameMapGemParamters;
use App\Flare\Models\GameSkill;
use App\Game\Gems\Values\GemTypeValue;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MapGemsController extends Controller
{
    public function index(): View
    {
        return view('admin.map-gems.index');
    }

    public function create(): View
    {
        return view('admin.map-gems.manage', [
            'gameMapGemParamters' => null,
            'gameMaps' => GameMap::orderBy('name')->get(),
            'gemTypes' => GemTypeValue::getNames(),
            'craftingSkills' => GameSkill::where('can_train', false)->orderBy('name')->get(),
        ]);
    }

    public function edit(GameMapGemParamters $gameMapGemParamters): View
    {
        return view('admin.map-gems.manage', [
            'gameMapGemParamters' => $gameMapGemParamters,
            'gameMaps' => GameMap::orderBy('name')->get(),
            'gemTypes' => GemTypeValue::getNames(),
            'craftingSkills' => GameSkill::where('can_train', false)->orderBy('name')->get(),
        ]);
    }

    public function show(GameMapGemParamters $gameMapGemParamters): View
    {
        return view('admin.map-gems.show', [
            'gameMapGemParamters' => $gameMapGemParamters->load('gameMap'),
        ]);
    }

    public function store(MapGemParamtersManagementRequest $request): RedirectResponse
    {
        $gameMapGemParamters = null;
        $validatedData = $request->validated();
        $validatedData['crafting_skill_ids'] = array_map(
            'intval',
            $validatedData['crafting_skill_ids'] ?? [],
        );

        if ($request->integer('id') !== 0) {
            $gameMapGemParamters = GameMapGemParamters::find($request->integer('id'));
        }

        if (is_null($gameMapGemParamters)) {
            $gameMapGemParamters = GameMapGemParamters::create($validatedData);
            $message = 'Created '.$gameMapGemParamters->name;
        } else {
            $gameMapGemParamters->update($validatedData);
            $message = 'Updated '.$gameMapGemParamters->name;
        }

        return response()
            ->redirectToRoute('admin.map-gems.show', ['gameMapGemParamters' => $gameMapGemParamters->id])
            ->with('success', $message);
    }

    /**
     * @codeCoverageIgnore
     */
    public function exportMapGems(): View
    {
        return view('admin.map-gems.export');
    }

    /**
     * @codeCoverageIgnore
     */
    public function importMapGems(): View
    {
        return view('admin.map-gems.import');
    }

    /**
     * @codeCoverageIgnore
     */
    public function export(): BinaryFileResponse
    {
        return Excel::download(new MapGemsExport, 'map-gems.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }

    /**
     * @codeCoverageIgnore
     */
    public function importData(MapGemParamtersImportRequest $request): RedirectResponse
    {
        Excel::import(new MapGemsImport, $request->file('map_gems_import'));

        return redirect()->back()->with('success', 'Imported map gem parameter data.');
    }
}
