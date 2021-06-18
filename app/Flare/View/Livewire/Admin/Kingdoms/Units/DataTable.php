<?php

namespace App\Flare\View\Livewire\Admin\Kingdoms\Units;

use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameBuildingUnit;
use App\Flare\Models\GameUnit;
use App\Flare\View\Livewire\Core\DataTables\WithSorting;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class DataTable extends Component
{
    use WithPagination;
    use WithSorting;

    protected $paginationTheme = 'bootstrap';

    public $search       = '';
    public $sortField    = 'name';
    public $perPage      = 10;
    public $editing      = false;
    public $building     = null;

    public function fetch() {
        if ($this->search !== '') {
            $this->page = 1;
        }

        if (!is_null($this->building)) {
            $gameUnits = GameBuildingUnit::where('game_building_id', $this->building->id)->join('game_units', function($join) {
                $query = $join->on('game_building_units.game_unit_id', '=', 'game_units.id');

                if ($this->search !== '') {
                    $query->where('name', 'like', '%'.$this->search.'%');
                }

                return $query;
            })->select('game_units.*');

            return $this->format($gameUnits->get());
        }

        $gameUnits = GameUnit::dataTableSearch($this->search)->get();

        return $this->format($gameUnits);
    }

    public function render() {
        return view('components.livewire.admin.kingdoms.units.data-table', [
            'units' => $this->fetch(),
        ]);
    }

    protected function format(Collection $collection) {
        $collection = $collection->transform(function($item) {
            $gameBuildingUnit = GameBuildingUnit::where('game_unit_id', $item->id)->first();

            $item->building_name  = $gameBuildingUnit->gameBuilding->name;
            $item->level_required = $gameBuildingUnit->required_level;

            return $item;
        });

        if ($this->sortBy === 'asc') {
            return $collection->sortBy($this->sortField)->paginate($this->perPage);
        } else {
            return $collection->sortByDesc($this->sortField)->paginate($this->perPage);
        }
    }
}
