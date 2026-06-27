<?php

namespace App\Admin\Controllers;

use App\Admin\Exports\MapGems\MapGemsExport;
use App\Admin\Import\MapGems\MapGemsImport;
use App\Admin\Requests\MapGemParamtersImportRequest;
use App\Admin\Requests\MapGemParamtersManagementRequest;
use App\Admin\Services\AdminGemRollService;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameMapGemParamter;
use App\Flare\Models\GameSkill;
use App\Game\Gems\Values\GemTypeValue;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MapGemsController extends Controller
{
    public function __construct(private readonly AdminGemRollService $adminGemRollService) {}

    public function index(): View
    {
        return view('admin.map-gems.index');
    }

    public function create(): View
    {
        return view('admin.map-gems.manage', [
            'gameMapGemParamter' => null,
            'gameMaps' => GameMap::orderBy('name')->get(),
            'gemTypes' => GemTypeValue::getNames(),
            'craftingSkills' => GameSkill::where('can_train', false)->orderBy('name')->get(),
        ]);
    }

    public function edit(GameMapGemParamter $gameMapGemParamter): View
    {
        return view('admin.map-gems.manage', [
            'gameMapGemParamter' => $gameMapGemParamter,
            'gameMaps' => GameMap::orderBy('name')->get(),
            'gemTypes' => GemTypeValue::getNames(),
            'craftingSkills' => GameSkill::where('can_train', false)->orderBy('name')->get(),
        ]);
    }

    public function show(GameMapGemParamter $gameMapGemParamter): View
    {
        return view('admin.map-gems.show', [
            'gameMapGemParamter' => $gameMapGemParamter->load('gameMap'),
        ]);
    }

    public function rolled(GameMapGemParamter $gameMapGemParamter): View|RedirectResponse
    {
        $gameMapGemParamter->load('gameMap', 'rolledGem.rolledByUser');

        if (is_null($gameMapGemParamter->rolled_gem_id) || is_null($gameMapGemParamter->rolledGem)) {
            return response()
                ->redirectToRoute('admin.map-gems.show', ['gameMapGemParamter' => $gameMapGemParamter->id])
                ->with('error', 'No rolled gem is available for this map gem profile.');
        }

        return view('admin.map-gems.rolled', [
            'gameMapGemParamter' => $gameMapGemParamter,
        ]);
    }

    public function roll(GameMapGemParamter $gameMapGemParamter): RedirectResponse
    {
        $gem = $this->adminGemRollService->rollMapGem($gameMapGemParamter, auth()->user());

        return response()
            ->redirectToRoute('admin.map-gems.show', ['gameMapGemParamter' => $gameMapGemParamter->id])
            ->with('success', 'Rolled '.$gem->name);
    }

    public function store(MapGemParamtersManagementRequest $request): RedirectResponse
    {
        $gameMapGemParamter = null;
        $validatedData = $request->validated();
        $validatedData['crafting_skill_ids'] = array_map(
            'intval',
            $validatedData['crafting_skill_ids'] ?? [],
        );

        if ($request->integer('id') !== 0) {
            $gameMapGemParamter = GameMapGemParamter::find($request->integer('id'));
        }

        if (is_null($gameMapGemParamter)) {
            $gameMapGemParamter = GameMapGemParamter::create($validatedData);
            $message = 'Created '.$gameMapGemParamter->name;
        } else {
            $gameMapGemParamter->update($validatedData);
            $message = 'Updated '.$gameMapGemParamter->name;
        }

        return response()
            ->redirectToRoute('admin.map-gems.show', ['gameMapGemParamter' => $gameMapGemParamter->id])
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
