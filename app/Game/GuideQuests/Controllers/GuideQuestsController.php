<?php

namespace App\Game\GuideQuests\Controllers;

use App\Flare\Models\Character;
use App\Flare\Models\GuideQuest;
use App\Flare\Models\QuestsCompleted;
use App\Flare\Models\User;
use App\Flare\View\Tables\TableColumn;
use App\Flare\View\Tables\TableQueryBuilder;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class GuideQuestsController extends Controller
{
    public function index(Request $request, User $user)
    {
        $character = $user->character;

        $rewardSortUsing = fn (string $field) => function (Builder $query, string $direction) use ($field) {
            $query->join('guide_quests', 'guide_quests.id', '=', 'quests_completed.guide_quest_id')
                ->orderBy('guide_quests.'.$field, $direction)
                ->select('quests_completed.*');
        };

        $columns = [
            new TableColumn(
                label: 'Name',
                field: 'guideQuest.name',
                searchable: true,
                html: true,
                render: fn ($row) => '<a href="'.route('completed.guide-quest', ['character' => $character->id, 'guideQuest' => $row->guide_quest_id]).'">'.e($row->guideQuest->name).'</a>',
            ),
            new TableColumn(
                label: 'Reward level',
                field: 'guideQuest.xp_reward',
                sortable: true,
                sortUsing: $rewardSortUsing('xp_reward'),
            ),
            new TableColumn(
                label: 'Reward level',
                field: 'guideQuest.gold_reward',
                sortable: true,
                sortUsing: $rewardSortUsing('gold_reward'),
            ),
            new TableColumn(
                label: 'Reward level',
                field: 'guideQuest.gold_dust_reward',
                sortable: true,
                sortUsing: $rewardSortUsing('gold_dust_reward'),
            ),
            new TableColumn(
                label: 'Reward level',
                field: 'guideQuest.shards_reward',
                sortable: true,
                sortUsing: $rewardSortUsing('shards_reward'),
            ),
        ];

        $paginator = TableQueryBuilder::paginate(
            QuestsCompleted::where('character_id', $character->id)->whereHas('guideQuest')->whereNull('quest_id'),
            $columns,
            $request
        );

        return view('game.guide-quests.completed-quests', [
            'character' => $character,
            'paginator' => $paginator,
            'columns' => $columns,
        ]);
    }

    public function show(Character $character, GuideQuest $guideQuest)
    {
        return view('admin.guide-quests.show', ['guideQuest' => $guideQuest]);
    }
}
