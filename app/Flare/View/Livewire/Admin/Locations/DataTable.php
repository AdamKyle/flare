<?php

namespace App\Flare\View\Livewire\Admin\Locations;

use Livewire\Component;
use App\Flare\View\Livewire\Core\DataTable as CoreDataTable;
use App\Flare\Models\Location;

class DataTable extends CoreDataTable
{
    public $adventureId = null;

    public function mount() {
        $this->sortField = 'name';

        $this->sortAsc   = false;
    }
    
    public function fetchLocations() {

        if ($this->sortField === 'game_maps.name') {
            $location = Location::join('game_maps', function($join) {
                $join = $join->on('locations.game_map_id', '=' ,'game_maps.id');

                if ($this->search !== '') {
                    dump($this->search);
                    $join->where('game_maps.name', 'like', '%'.$this->search.'%');
                }

                return $join;
            });

            if (!is_null($this->adventureId)) {
                $location = $location->join('adventure_location', function($join) {
                    return $join->on('locations.id', '=', 'adventure_location.location_id')->where('adventure_location.adventure_id', $this->adventureId);
                });
            }

            $column = ($this->sortField !== 'game_maps.name' ? 'locations.name' : 'game_maps.name');
            
            return $location->orderBy($column, $this->sortAsc ? 'asc' : 'desc')
                            ->select('locations.*')
                            ->paginate($this->perPage);
        } else  {
            $location = Location::dataTableSearch($this->search);

            if (!is_null($this->adventureId)) {
                $location = $location->join('adventure_location', function($join) {
                    return $join->on('locations.id', '=', 'adventure_location.location_id')->where('adventure_location.adventure_id', $this->adventureId);
                });
            }

            $column = 'locations.' . $this->sortField;
            

            return $location->orderBy($column, $this->sortAsc ? 'asc' : 'desc')
                            ->select('locations.*')
                            ->paginate($this->perPage);
        }
    }

    public function render()
    {
        return view('components.livewire.admin.locations.data-table', [
            'locations' => $this->fetchLocations(),
        ]);
    }
}
