<?php

namespace App\Flare\View\Livewire\Admin\Kingdoms\Buildings;

use App\Flare\Models\GameBuilding;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class BuildingsTable extends DataTableComponent {

    public function configure(): void {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder {
        return GameBuilding::query();
    }

    public function columns(): array {
        return [
            Column::make('Name')->searchable()->format(function ($value, $row) {
                $buildingId = GameBuilding::where('name', $value)->first()->id;

                if (!is_null(auth()->user())) {
                    if (auth()->user()->hasRole('Admin')) {
                        return '<a href="/admin/kingdoms/buildings/' . $buildingId . '">' . $row->name . '</a>';
                    }
                }

                return '<a href="/information/building/'. $buildingId.'" >'.$row->name . '</a>';
            })->html(),
            Column::make('Max Level')->sortable(),
            Column::make('Base Durability')->sortable(),
            Column::make('Base Defence')->sortable(),
        ];
    }
}
