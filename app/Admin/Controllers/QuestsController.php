<?php

namespace App\Admin\Controllers;

use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Flare\Models\Npc;
use App\Game\Skills\Values\SkillTypeValue;
use Cache;
use App\Flare\Models\Character;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Controller;
use App\Admin\Exports\Quests\QuestsExport;
use App\Admin\Import\Quests\QuestsImport;
use App\Admin\Requests\QuestsImportRequest;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Quest;

class QuestsController extends Controller {

    public function index() {
        return view('admin.quests.index');
    }

    public function show(Quest $quest) {
        $skill = null;

        if ($quest->unlocks_skill) {
            $skill = GameSkill::where('type', $quest->unlocks_skill_type)->where('is_locked', true)->first();
        }

        return view('admin.quests.show', [
            'quest'       => $quest,
            'lockedSkill' => $skill,
        ]);
    }

    public function create() {
        return view('admin.quests.manage', [
            'quest'          => null,
            'editing'        => false,
            'npcs'           => Npc::pluck('real_name', 'id')->toArray(),
            'questItems'     => Item::where('type', 'quest')->pluck('name', 'id')->toArray(),
            'quests'         => Quest::pluck('name', 'id')->toArray(),
            'gameMaps'       => GameMap::pluck('name', 'id')->toArray(),
            'skillTypes'     => SkillTypeValue::getValues(),
        ]);
    }

    public function edit(Quest $quest) {
        return view('admin.quests.manage', [
            'quest'          => $quest,
            'editing'        => true,
            'npcs'           => Npc::pluck('real_name', 'id')->toArray(),
            'questItems'     => Item::where('type', 'quest')->pluck('name', 'id')->toArray(),
            'quests'         => Quest::pluck('name', 'id')->toArray(),
            'gameMaps'       => GameMap::pluck('name', 'id')->toArray(),
            'skillTypes'     => SkillTypeValue::getValues(),
        ]);
    }

    public function store(Request $request) {
        dd($request->all());
    }

    public function exportQuests() {
        return view('admin.quests.export');
    }

    public function importQuests() {
        return view('admin.quests.import');
    }

    /**
     * @codeCoverageIgnore
     */
    public function export() {
        $response = Excel::download(new QuestsExport, 'quests.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        ob_end_clean();

        return $response;
    }

    /**
     * @codeCoverageIgnore
     */
    public function import(QuestsImportRequest $request) {
        Excel::import(new QuestsImport, $request->quests_import);

        Cache::put('character-quest-reset', Character::all()->pluck('id')->toArray());

        return redirect()->back()->with('success', 'imported quest data.');
    }
}
