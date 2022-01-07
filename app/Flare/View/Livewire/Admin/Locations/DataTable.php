<?php

namespace App\Flare\View\Livewire\Admin\Locations;

use Livewire\Component;
use App\Flare\Models\Location;
use Livewire\WithPagination;
use App\Flare\View\Livewire\Core\DataTables\WithSorting;

class DataTable extends Component
{

    use WithPagination, WithSorting;

    public $adventureId = null;
    public $search      = '';
    public $sortField   = 'name';
    public $perPage     = 10;
    public $gameMapId   = null;
    public $only        = null;

    protected $paginationTheme = 'bootstrap';

    public function fetchLocations() {

        if ($this->search !== '') {
            $this->page = 1;
        }

        if ($this->sortField === 'game_maps.name') {
            $location = Location::join('game_maps', function($join) {
                $join = $join->on('locations.game_map_id', '=' ,'game_maps.id');

                if ($this->search !== '') {
                    $join->where('game_maps.name', 'like', '%'.$this->search.'%');
                }

                return $join;
            });

            if (!is_null($this->adventureId)) {
                $location = $location->join('adventures', function($join) {
                    return $join->on('locations.id', '=', 'adventures.location_id')->where('adventures.id', $this->adventureId);
                });
            }

            $column = ($this->sortField !== 'game_maps.name' ? 'locations.name' : 'game_maps.name');

        } else  {
            $location = Location::dataTableSearch($this->search);

            if (!is_null($this->adventureId)) {
                $location = $location->join('adventures', function($join) {
                    return $join->on('locations.id', '=', 'adventures.location_id')->where('adventures.id', $this->adventureId);
                });
            }

            $column = 'locations.' . $this->sortField;
        }

        if (!is_null($this->gameMapId)) {
            $location = $location->where('game_map_id', $this->gameMapId);
        }

        if ($this->only === 'special_locations') {
            $location = $location->whereNotNull('enemy_strength_type');
        }

        $locations = $location->orderBy($column, $this->sortBy)
                              ->select('locations.*')
                              ->paginate($this->perPage);

        return $locations;
    }

    public function render()
    {
        return view('components.livewire.admin.locations.data-table', [
            'locations' => $this->fetchLocations(),
        ]);
    }
}
