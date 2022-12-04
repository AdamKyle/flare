<?php

namespace App\Flare\View\Livewire\Admin\ClassSpecials;

use App\Flare\Models\GameClass;
use App\Flare\Models\GameClassSpecial;
use App\Flare\Models\GameMap;
use App\Flare\Models\ItemAffix;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class ClassSpecialsTable extends DataTableComponent {

    public function configure(): void {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder {
        return GameClassSpecial::query();
    }

    public function filters(): array {
        return [
            SelectFilter::make('Types')
                ->options($this->buildOptions())
                ->filter(function(Builder $builder, string $value) {
                    return $builder->where('game_class_id', $value);
                }),
        ];
    }

    protected function buildOptions(): array {
        return GameClass::pluck('name', 'id')->toArray();
    }

    public function columns(): array {
        return [
            Column::make('Name')->searchable()->format(function ($value, $row) {
                $gameClassSpecialId = GameClassSpecial::where('name', $value)->first()->id;

                if (!is_null(auth()->user())) {
                    if (auth()->user()->hasRole('Admin')) {
                        return '<a href="/admin/class-special/'. $gameClassSpecialId.'">'.$row->name . '</a>';
                    }
                }

                return '<a href="/information/class-specials/'. $gameClassSpecialId.'" target="_blank">  <i class="fas fa-external-link-alt"></i> '.$row->name . '</a>';
            })->html(),

            Column::make('For Class', 'game_class_id')->sortable()->format(function ($value) {
                return GameClass::find($value)->name;
            }),

            Column::make('Class Rank Level', 'requires_class_rank_level')->sortable()->format(function ($value) {
                return number_format($value);
            }),

            Column::make('Description', 'description')->sortable()->format(function ($value) {
                return $value;
            }),
        ];
    }
}
