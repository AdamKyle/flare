<?php

namespace App\Flare\View\Livewire\Character\CompletedGuideQuests;

use App\Flare\Models\QuestsCompleted;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class CompletedGuideQuests extends DataTableComponent {

    public function configure(): void {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder {
        return QuestsCompleted::where('character_id', auth()->user()->character->id)->whereHas('guideQuest')->whereNull('quest_id');
    }

    public function columns(): array {
        return [
            Column::make('id', 'id')->hideIf(true),
            Column::make('id', 'guide_quest_id')->hideIf(true),
            Column::make('Name', 'guideQuest.name')->searchable()->format(
                fn($value, $row, Column $column)  => '<a href="/game/completed-guide-quest/'.auth()->user()->character->id.'/'.$row->guide_quest_id.'">'.$value.'</a>'
            )->html(),
            Column::make('Reward level', 'guideQuest.xp_reward')->sortable(),
            Column::make('Reward level', 'guideQuest.gold_reward')->sortable(),
            Column::make('Reward level', 'guideQuest.gold_dust_reward')->sortable(),
            Column::make('Reward level', 'guideQuest.shards_reward')->sortable(),
        ];
    }
}
