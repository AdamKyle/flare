<?php

namespace App\Flare\View\Livewire\Admin\Classes;

use App\Flare\Models\GameClass;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class ClassesTable extends DataTableComponent
{
    public function builder(): Builder {
        return GameClass::query();
    }

    public function configure(): void {
        $this->setPrimaryKey('id');
    }

    public function columns(): array {
        return [
            Column::make('Name')->searchable()->format(function ($value, $row) {
                $gameClass = GameClass::where('name', $value)->first()->id;

                if (is_null(auth()->user())) {
                    '<a href="/information/classes/'. $gameClass.'">'.$row->name .'</a>';
                }

                if (!is_null(auth()->user())) {
                    if (auth()->user()->hasRole('Admin')) {
                        return '<a href="/admin/classes/'. $gameClass.'">'.$row->name .'</a>';
                    }
                }

                return '<a href="/information/class/'. $gameClass.'">'.$row->name .'</a>';

            })->html(),
            Column::make('To Hit', 'to_hit_stat')->sortable(),
            Column::make('Damage Stat', 'damage_stat')->sortable(),
            Column::make('Str Mod', 'str_mod')->sortable(),
            Column::make('Dex Mod', 'dex_mod')->sortable(),
            Column::make('Int Mod', 'int_mod')->sortable(),
            Column::make('Agi Mod', 'agi_mod')->sortable(),
            Column::make('Chr Mod', 'chr_mod')->sortable(),
            Column::make('Dur Mod', 'dur_mod')->sortable(),
            Column::make('Focus Mod', 'focus_mod')->sortable(),
        ];
    }
}
