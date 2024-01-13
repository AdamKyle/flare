<?php

namespace App\Flare\View\Livewire\Character\CompletedQuests;

use App\Flare\Models\QuestsCompleted;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class CompletedQuests extends DataTableComponent {

    public function configure(): void {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder {
        return QuestsCompleted::where('character_id', auth()->user()->character->id)->whereNull('guide_quest_id');
    }

    public function columns(): array {
       return [
           Column::make('id', 'id')->hideIf(true),
           Column::make('Name', 'quest.name')->searchable()->format(
               fn($value, $row, Column $column)  => '<a href="/game/completed-quest/'.auth()->user()->character->id.'/'.$row->id.'">'.$value.'</a>'
           )->html(),
           Column::make('Map Name', 'quest.npc.gameMap.name')->searchable(),
       ];
    }
}
