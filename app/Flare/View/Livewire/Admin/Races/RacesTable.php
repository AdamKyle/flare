<?php

namespace App\Flare\View\Livewire\Admin\Races;

use App\Flare\Models\GameRace;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class RacesTable extends DataTableComponent
{
    public function builder(): Builder {
        return GameRace::query();
    }

    public function configure(): void {
        $this->setPrimaryKey('id');
    }

    public function columns(): array {
        return [
            Column::make('Name')->searchable()->format(function ($value, $row) {
                $gameRace = GameRace::where('name', $value)->first()->id;

                if (is_null(auth()->user())) {
                    '<a href="/information/races/'. $gameRace.'">'.$row->name .'</a>';
                }

                if (!is_null(auth()->user())) {
                    if (auth()->user()->hasRole('Admin')) {
                        return '<a href="/admin/races/'. $gameRace.'">'.$row->name .'</a>';
                    }
                }

                return '<a href="/information/race/'. $gameRace.'">'.$row->name .'</a>';

            })->html(),
            Column::make('Str Mod', 'str_mod')->sortable(),
            Column::make('Dex Mod', 'dex_mod')->sortable(),
            Column::make('Int Mod', 'int_mod')->sortable(),
            Column::make('Agi Mod', 'agi_mod')->sortable(),
            Column::make('Chr Mod', 'chr_mod')->sortable(),
            Column::make('Dur Mod', 'dur_mod')->sortable(),
            Column::make('Focus Mod', 'chr_mod')->sortable(),
        ];
    }
}
