<?php

namespace App\Admin\Controllers;

use App\Admin\Requests\MonsterManagementRequest;
use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Flare\Services\BuildMonsterCacheService;
use App\Flare\Traits\Controllers\MonstersShowInformation;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Flare\Models\Monster;
use App\Admin\Import\Monsters\MonstersImport;
use App\Admin\Exports\Monsters\MonstersExport;
use App\Admin\Requests\MonstersImport as MonstersImportRequest;

class MonstersController extends Controller {

    use MonstersShowInformation;

    private $monsterCache;

    public function __construct(BuildMonsterCacheService $monsterCache) {
        $this->middleware('is.admin')->except([
            'show'
        ]);

        $this->monsterCache = $monsterCache;
    }

    public function index() {
        return view('admin.monsters.monsters', [
            'gameMapNames' => GameMap::all()->pluck('name')->toArray(),
        ]);
    }

    public function show(Monster $monster) {
        return $this->renderMonsterShow($monster);
    }

    public function create() {
        return view('admin.monsters.manage', [
            'monster' => null,
            'gameMaps'   => GameMap::all(),
            'questItems' => Item::where('type', 'quest')->get(),
        ]);
    }

    public function edit(Monster $monster) {
        return view('admin.monsters.manage', [
            'monster'    => $monster,
            'gameMaps'   => GameMap::all(),
            'questItems' => Item::where('type', 'quest')->get(),
        ]);
    }

    public function exportItems() {
        return view('admin.monsters.export');
    }

    public function importItems() {
        return view('admin.monsters.import');
    }

    /**
     * @codeCoverageIgnore
     */
    public function export() {
        $response = Excel::download(new MonstersExport, 'monsters.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        ob_end_clean();

        return $response;
    }

    /**
     * @codeCoverageIgnore
     */
    public function importData(MonstersImportRequest $request) {
        Excel::import(new MonstersImport, $request->monsters_import);

        $this->monsterCache->buildCache();

        event(new GlobalMessageEvent('Monsters have been updated (or created), please refresh to see the new list.'));

        return redirect()->back()->with('success', 'imported monster data.');
    }

    public function store(MonsterManagementRequest $request) {
        $data = $this->cleanRequestData($request->all());

        $monster = Monster::updateOrCreate(['id' => $data['id']], $data);

        $message = 'Created: ' . $monster->name;

        if ($data['id'] !== 0) {
            $message = 'Updated: ' . $monster->name;
        }

        return response()->redirectToRoute('monsters.monster', ['monster' => $monster->id])->with('success', $message);
    }

    protected function cleanRequestData(array $params): array {


        if (!filter_var($params['is_celestial_entity'], FILTER_VALIDATE_BOOLEAN)) {
            $params['is_celestial_entity'] = false;
            $params['gold_cost']           = 0;
            $params['gold_dust_cost']      = 0;
            $params['shards']              = 0;
        }

        if (!filter_var($params['can_cast'], FILTER_VALIDATE_BOOLEAN)) {
            $params['can_cast']         = false;
            $params['max_spell_damage'] = 0;
        }

        if (!filter_var($params['can_use_artifacts'], FILTER_VALIDATE_BOOLEAN)) {
            $params['can_use_artifacts']   = false;
            $params['max_artifact_damage'] = 0;
        }

        if (is_null($params['quest_item_id'])) {
            $params['quest_item_drop_chance'] = 0.0;
        }

        return $params;
    }
}
