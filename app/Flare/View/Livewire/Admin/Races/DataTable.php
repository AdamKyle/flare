<?php

namespace App\Flare\View\Livewire\Admin\Races;

use App\Flare\Models\GameRace;
use Livewire\Component;
use Livewire\WithPagination;
use App\Flare\View\Livewire\Core\DataTables\WithSorting;

class DataTable extends Component
{
    use WithPagination, WithSorting;

    public $search  = '';

    public $sortField = 'name';

    public $perPage = 10;

    protected $paginationTheme = 'bootstrap';

    public function fetchRaces() {
        if ($this->search !== '') {
            $this->page = 1;

            return GameRace::where('name', 'like', '%'.$this->search.'%')
                           ->orderBy($this->sortField, $this->sortBy ? 'asc' : 'desc')
                           ->paginate($this->perPage);
        }

        return GameRace::orderBy($this->sortField, $this->sortBy)
                       ->paginate($this->perPage);
    }

    public function render()
    {
        return view('components.livewire.admin.races.data-table', [
            'races' => $this->fetchRaces()
        ]);
    }
}
