<?php

namespace App\Flare\View\Livewire\Admin\Quests;

use App\Flare\Models\Quest;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class QuestsTable extends DataTableComponent {


    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder {
        return Quest::query();
    }

    public function columns(): array
    {
        return [
            Column::make('id')->hideIf(true),
            Column::make('Name')->searchable()->format(function($value, $row) {
                return '<a href="/admin/quests/'.$row->id.'">'.$value.'</a>';
            })->html(),
            Column::make('From NPC', 'npc_id')->searchable()->format(function($value, $row) {
                return '<a href="/admin/npcs/'.$row->npc_id.'">'.$row->npc->real_name.'</a>';
            })->html(),
        ];
    }
}
