<?php

namespace App\Flare\View\Livewire\Admin\Classes;

use App\Flare\Models\GameClass;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class ClassesTable extends DataTableComponent
{
    public function builder(): Builder
    {
        return GameClass::query();
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function columns(): array
    {
        return [
            Column::make('Name')->searchable()->format(function ($value, $row) {
                $gameClass = GameClass::where('name', $value)->first();
                $isSpecial = ! is_null($gameClass->primary_required_class_id);

                if (is_null(auth()->user())) {
                    '<a href="/information/classes/'.$gameClass->id.'"> '.($isSpecial ? '<i class="fas fa-star text-yellow-700 dark:text-yellow-500"></i> ' : '').$row->name.'</a>';
                }

                if (! is_null(auth()->user())) {
                    if (auth()->user()->hasRole('Admin')) {
                        return '<a href="/admin/classes/'.$gameClass->id.'"> '.($isSpecial ? '<i class="fas fa-star text-yellow-700 dark:text-yellow-500"></i> ' : '').$row->name.'</a>';
                    }
                }

                return '<a href="/information/class/'.$gameClass->id.'"> '.($isSpecial ? '<i class="fas fa-star text-yellow-700 dark:text-yellow-500"></i> ' : '').$row->name.'</a>';

            })->html(),
            Column::make('To Hit', 'to_hit_stat')->sortable(),
            Column::make('Damage Stat', 'damage_stat')->sortable(),
            Column::make('Str Mod', 'str_mod')->format(function ($value) {
                return is_null($value) ? 0 : $value;
            })->sortable(),
            Column::make('Dex Mod', 'dex_mod')->format(function ($value) {
                return is_null($value) ? 0 : $value;
            })->sortable(),
            Column::make('Int Mod', 'int_mod')->format(function ($value) {
                return is_null($value) ? 0 : $value;
            })->sortable(),
            Column::make('Agi Mod', 'agi_mod')->format(function ($value) {
                return is_null($value) ? 0 : $value;
            })->sortable(),
            Column::make('Chr Mod', 'chr_mod')->format(function ($value) {
                return is_null($value) ? 0 : $value;
            })->sortable(),
            Column::make('Dur Mod', 'dur_mod')->format(function ($value) {
                return is_null($value) ? 0 : $value;
            })->sortable(),
            Column::make('Focus Mod', 'focus_mod')->format(function ($value) {
                return is_null($value) ? 0 : $value;
            })->sortable(),
            Column::make('Is Locked', 'name')->format(function ($value, $row) {
                $gameClass = GameClass::where('name', $value)->first();

                if (! is_null($gameClass->primary_required_class_id) && ! is_null($gameClass->secondary_required_class_id)) {
                    return 'Yes';
                }

                return 'No';

            })->html(),
        ];
    }
}
