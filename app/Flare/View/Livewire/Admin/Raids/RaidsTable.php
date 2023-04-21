<?php

namespace App\Flare\View\Livewire\Admin\Raids;

use App\Flare\Models\GameMap;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Models\Raid;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class RaidsTable extends DataTableComponent {

    public function configure(): void {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder {
        return Raid::query();
    }

    public function columns(): array {
        return [
            Column::make('Name')->searchable()->format(function ($value) {
                $raid = Raid::where('name', $value)->first();

                return '<a href="/information/raids/'. $raid->id.'">  <i class="fas fa-external-link-alt"></i> '.$raid->name . '</a>';
            })->html(),

            Column::make('Raid Boss', 'raid_boss_id')->format(function ($value) {
                $monster = Monster::find($value);

                return '<a href="/information/monster/'. $monster->id.'">  <i class="fas fa-external-link-alt"></i> '.$monster->name . '</a>';
            })->html(),
            Column::make('Raid Boss Location', 'raid_boss_location_id')->format(function ($value) {
                $location = Location::find($value);

                return '<a href="/information/location/'. $location->id.'">  <i class="fas fa-external-link-alt"></i> '.$location->name . '</a>';
            })->html(),
            Column::make('Has Corrupted Locations', 'corrupted_location_ids')->format(function ($value, $row) {
                $raid = Raid::where('name', $row->name)->first();

                return !empty($raid->corrupted_location_ids) ? 'Yes' : 'No';
            })->html(),
        ];
    }
}
