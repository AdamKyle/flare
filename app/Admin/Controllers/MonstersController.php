<?php

namespace App\Admin\Controllers;

use App\Flare\Models\GameMap;
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

    public function publish(Monster $monster) {
        $monster->update(['published' => true]);

        $this->monsterCache->buildCache();

        event(new GlobalMessageEvent('Monsters have been updated (or created), please refresh to see the new list.'));

        return redirect()->to(route('monsters.list'))->with('success', 'Monster was published.');
    }
}
