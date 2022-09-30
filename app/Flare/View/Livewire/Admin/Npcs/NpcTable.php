<?php

namespace App\Flare\View\Livewire\Admin\Npcs;

use App\Flare\Models\Npc;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class NpcTable extends DataTableComponent {

    public function configure(): void {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder {
        return Npc::query();
    }

    public function columns(): array {
        return [
            Column::make('Name')->searchable()->format(function ($value, $row) {
                $npc = Npc::where('name', $value)->first();

                if (!is_null(auth()->user())) {
                    if (auth()->user()->hasRole('Admin')) {
                        return '<a href="/admin/npcs/' . $npc->id . '">' . $npc->real_name . '</a>';
                    }
                }

                return '<a href="/information/npcs/'. $npc->id.'" >'.$npc->real_name . '</a>';
            })->html(),
            Column::make('Type')->format(function ($value, $row) {
                $npc = Npc::where('name', $row->name)->first();

                return $npc->type()->getNamedValue();
            })->html(),
            Column::make('Plane', 'game_map_id')->format(function ($value, $row) {
                $npc = Npc::where('name', $row->name)->first();

                return $npc->gameMap->name;
            })->html(),
            Column::make('X', 'x_position'),
            Column::make('Y', 'y_position'),
        ];
    }
}
