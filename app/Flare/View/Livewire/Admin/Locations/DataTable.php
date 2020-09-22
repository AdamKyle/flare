<?php

namespace App\Flare\View\Livewire\Admin\Locations;

use Livewire\Component;
use App\Flare\View\Livewire\Core\DataTable as CoreDataTable;
use App\Flare\Models\Location;

class DataTable extends CoreDataTable
{
    public function mount() {
        $this->sortField = 'name';

        $this->sortAsc   = false;
    }
    

    public function fetchLocations() {

        if ($this->sortField === 'game_maps.name') {
            return Location::join('game_maps', 'locations.game_map_id', '=' ,'game_maps.id')
                            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                            ->select('locations.*')
                            ->paginate($this->perPage);
        }

        return Location::dataTableSearch($this->search)
                        ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                        ->paginate($this->perPage);
    }

    public function render()
    {
        return view('components.livewire.admin.locations.data-table', [
            'locations' => $this->fetchLocations(),
        ]);
    }
}
