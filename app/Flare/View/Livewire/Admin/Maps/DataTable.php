<?php

namespace App\Flare\View\Livewire\Admin\Maps;

use Livewire\Component;
use Livewire\WithPagination;
use App\Flare\Models\GameMap;
use App\Flare\View\Livewire\Core\DataTables\WithSorting;

class DataTable extends Component
{
    use WithPagination, WithSorting;

    public $search    = '';
    public $sortField = 'id';
    public $perPage   = 10;

    protected $paginationTheme = 'bootstrap';

    public function fetchMaps() {
        if ($this->search !== '') {
            $this->page = 1;
        }

        $maps = GameMap::dataTableSearch($this->search)->get();

        $maps->transform(function($map) {
            $map->characters_using = $map->maps->count();

            return $map;
        });

        if ($this->sortBy === 'asc') {
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
