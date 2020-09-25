<?php

namespace App\Flare\View\Livewire\Admin\Maps;

use Livewire\Component;
use App\Flare\View\Livewire\Core\DataTable as CoreDataTable;
use App\Flare\Models\GameMap;

class DataTable extends CoreDataTable
{
    public function mount() {
        $this->sortField = 'name';

        $this->sortAsc   = false;
    }

    public function fetchMaps() {
        $maps = GameMap::dataTableSearch($this->search)->get();

        $maps->transform(function($map) {
            $map->characters_using = $map->maps->count();

            return $map;
        });

        if ($this->sortAsc) {
            return $maps->sortBy($this->sortField)->paginate($this->perPage);
        }

        return $maps->sortByDesc($this->sortField)->paginate($this->perPage);
    }

    public function render()
    {
        return view('components.livewire.admin.maps.data-table', [
            'maps' => $this->fetchMaps(),
        ]);
    }
}
