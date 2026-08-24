<?php

namespace App\Game\Quests\Controllers;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\QuestsCompleted;
use App\Flare\Models\User;
use App\Flare\Tables\TableColumn;
use App\Flare\Tables\TableQueryBuilder;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class QuestsController extends Controller
{
    public function index(Request $request, User $user)
    {
        $character = $user->character;

        $columns = [
            new TableColumn(
                label: 'Name',
                field: 'quest.name',
                searchable: true,
                html: true,
                render: fn ($row) => '<a href="'.route('completed.quest', ['character' => $character->id, 'questsCompleted' => $row->id]).'">'.e($row->quest->name).'</a>',
            ),
            new TableColumn(
                label: 'Map Name',
                field: 'quest.npc.gameMap.name',
                searchable: true,
            ),
        ];

        $paginator = TableQueryBuilder::paginate(
            QuestsCompleted::where('character_id', $character->id)->whereNull('guide_quest_id'),
            $columns,
            $request
        );

        return view('game.quests.completed_quests', [
            'character' => $character,
            'paginator' => $paginator,
            'columns' => $columns,
        ]);
    }

    public function show(Character $character, QuestsCompleted $questsCompleted)
    {
        abort_unless($questsCompleted->character_id === $character->id, 404);

        $skill = null;

        if ($questsCompleted->quest->unlocks_skill) {
            $skill = GameSkill::where('type', $questsCompleted->quest->unlocks_skill_type)->where('is_locked', true)->first();
            $skill = $character->skills()->where('game_skill_id', $skill->id)->first();
        }

        return view('admin.quests.show', [
            'quest' => $questsCompleted->quest,
            'character' => $character,
            'lockedSkill' => $skill,
        ]);
    }
}
