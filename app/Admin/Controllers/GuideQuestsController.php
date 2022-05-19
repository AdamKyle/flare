<?php

namespace App\Admin\Controllers;

use App\Admin\Requests\GuideQuestManagement;
use App\Admin\Services\GuideQuestService;
use App\Flare\Models\GuideQuest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Models\Quest;
use App\Flare\Models\GameClass;

class GuideQuestsController extends Controller {

    private GuideQuestService $guideQuestService;

    public function __construct(GuideQuestService $guideQuestService) {
        $this->guideQuestService = $guideQuestService;
    }

    public function index() {
        return view('admin.guide-quests.index');
    }

    public function show(GuideQuest $guideQuest) {
        return view('admin.guide-quests.show', [
            'guideQuest' => $guideQuest,
        ]);
    }

    public function create() {
        return view('admin.guide-quests.manage', [
            'guideQuest'  => null,
            'gameSkills'  => GameSkill::pluck('name', 'id')->toArray(),
            'gameMaps'    => GameMap::where('name', '!=', 'Purgatory')->pluck('name', 'id')->toArray(),
            'quests'      => Quest::pluck('name', 'id')->toArray(),
            'questItems'  => Item::where('type', 'quest')->pluck('name', 'id')->toArray(),
        ]);
    }

    public function store(GuideQuestManagement $request) {
        $params = $this->guideQuestService->cleanRequest($request->all());

        $guideQuest = GuideQuest::updateOrCreate(['id' => $params['id']], $params);

        return response()->redirectToRoute('admin.guide-quests.show', ['guideQuest' => $guideQuest->id])->with('success', 'Saved Guide Quest');
    }

    public function edit(GuideQuest $guideQuest) {
        return view('admin.guide-quests.manage', [
            'guideQuest'  => $guideQuest,
            'gameSkills'  => GameSkill::pluck('name', 'id')->toArray(),
            'gameMaps'    => GameMap::where('name', '!=', 'Purgatory')->pluck('name', 'id')->toArray(),
            'quests'      => Quest::pluck('name', 'id')->toArray(),
            'questItems'  => Item::where('type', 'quest')->pluck('name', 'id')->toArray(),
        ]);
    }
}
