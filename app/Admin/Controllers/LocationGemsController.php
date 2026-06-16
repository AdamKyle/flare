<?php

namespace App\Admin\Controllers;

use App\Admin\Exports\LocationGems\LocationGemsExport;
use App\Admin\Import\LocationGems\LocationGemsImport;
use App\Admin\Requests\LocationGemParamtersImportRequest;
use App\Admin\Requests\LocationGemParamtersManagementRequest;
use App\Flare\Models\GameLocationGemParamters;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Location;
use App\Flare\Values\MapNameValue;
use App\Game\Gems\Values\GemTypeValue;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LocationGemsController extends Controller
{
    public function index(): View
    {
        return view('admin.location-gems.index');
    }

    public function create(): View
    {
        return view('admin.location-gems.manage', [
            'gameLocationGemParamters' => null,
            'locations' => $this->eligibleLocations(),
            'gemTypes' => GemTypeValue::getNames(),
            'craftingSkills' => GameSkill::where('can_train', false)->orderBy('name')->get(),
        ]);
    }

    public function edit(GameLocationGemParamters $gameLocationGemParamters): View
    {
        return view('admin.location-gems.manage', [
            'gameLocationGemParamters' => $gameLocationGemParamters,
            'locations' => $this->eligibleLocations(),
            'gemTypes' => GemTypeValue::getNames(),
            'craftingSkills' => GameSkill::where('can_train', false)->orderBy('name')->get(),
        ]);
    }

    private function eligibleLocations(): Collection
    {
        $planeOrder = [
            MapNameValue::SURFACE => 0,
            MapNameValue::LABYRINTH => 1,
            MapNameValue::DUNGEONS => 2,
            MapNameValue::SHADOW_PLANE => 3,
            MapNameValue::HELL => 4,
            MapNameValue::PURGATORY => 5,
            MapNameValue::TWISTED_MEMORIES => 6,
            MapNameValue::ICE_PLANE => 7,
            MapNameValue::DELUSIONAL_MEMORIES => 8,
        ];

        return Location::with('map')
            ->eligibleForLocationGems()
            ->get()
            ->sort(function (Location $a, Location $b) use ($planeOrder) {
                $aSpecial = is_null($a->type) ? 0 : 1;
                $bSpecial = is_null($b->type) ? 0 : 1;
                if ($aSpecial !== $bSpecial) {
                    return $aSpecial - $bSpecial;
                }
                $aPlane = $planeOrder[$a->map->name ?? ''] ?? 999;
                $bPlane = $planeOrder[$b->map->name ?? ''] ?? 999;
                if ($aPlane !== $bPlane) {
                    return $aPlane - $bPlane;
                }

                return strcmp($a->name, $b->name);
            })
            ->values();
    }

    public function show(GameLocationGemParamters $gameLocationGemParamters): View
    {
        return view('admin.location-gems.show', [
            'gameLocationGemParamters' => $gameLocationGemParamters->load('location.map'),
        ]);
    }

    public function store(LocationGemParamtersManagementRequest $request): RedirectResponse
    {
        $gameLocationGemParamters = null;
        $validatedData = $request->validated();
        $validatedData['crafting_skill_ids'] = array_map(
            'intval',
            $validatedData['crafting_skill_ids'] ?? [],
        );

        if ($request->integer('id') !== 0) {
            $gameLocationGemParamters = GameLocationGemParamters::find($request->integer('id'));
        }

        if (is_null($gameLocationGemParamters)) {
            $gameLocationGemParamters = GameLocationGemParamters::create($validatedData);
            $message = 'Created '.$gameLocationGemParamters->name;
        } else {
            $gameLocationGemParamters->update($validatedData);
            $message = 'Updated '.$gameLocationGemParamters->name;
        }

        return response()
            ->redirectToRoute('admin.location-gems.show', ['gameLocationGemParamters' => $gameLocationGemParamters->id])
            ->with('success', $message);
    }

    /**
     * @codeCoverageIgnore
     */
    public function exportLocationGems(): View
    {
        return view('admin.location-gems.export');
    }

    /**
     * @codeCoverageIgnore
     */
    public function importLocationGems(): View
    {
        return view('admin.location-gems.import');
    }

    /**
     * @codeCoverageIgnore
     */
    public function export(): BinaryFileResponse
    {
        return Excel::download(new LocationGemsExport, 'location-gems.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }

    /**
     * @codeCoverageIgnore
     */
    public function importData(LocationGemParamtersImportRequest $request): RedirectResponse
    {
        Excel::import(new LocationGemsImport, $request->file('location_gems_import'));

        return redirect()->back()->with('success', 'Imported location gem parameter data.');
    }
}
