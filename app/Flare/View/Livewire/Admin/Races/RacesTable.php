<?php

namespace App\Flare\View\Livewire\Admin\Races;

use App\Flare\Models\GameRace;
use App\Flare\Models\GuideQuest;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
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
                    if (auth()->user->hasRole('Admin')) {
                        return '<a href="/admin/races/'. $gameRace.'">'.$row->name .'</a>';
                    }
                }

                return '<a href="/information/races/'. $gameRace.'">'.$row->name .'</a>';

            })->html(),
            Column::make('Strength Modifier', 'str_mod')->sortable(),
            Column::make('Dexterity Modifier', 'dex_mod')->sortable(),
            Column::make('Intelligence Modifier', 'int_mod')->sortable(),
            Column::make('Agility Modifier', 'agi_mod')->sortable(),
            Column::make('Charisma Modifier', 'chr_mod')->sortable(),
            Column::make('Durability Modifier', 'dur_mod')->sortable(),
            Column::make('Charisma Modifier', 'chr_mod')->sortable(),
        ];
    }
}
