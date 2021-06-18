<?php

namespace App\Flare\View\Livewire\Admin\Classes;

use App\Flare\Models\GameClass;
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

    public function fetchClasses() {
        if ($this->search !== '') {
            $this->page = 1;
        }

        if ($this->search !== '') {
            return GameClass::where('name', 'like', '%'.$this->search.'%')
                           ->orderBy($this->sortField, $this->sortBy ? 'asc' : 'desc')
                           ->paginate($this->perPage);
        }

        return GameClass::orderBy($this->sortField, $this->sortBy)
                        ->paginate($this->perPage);
    }

    public function render()
    {
        return view('components.livewire.admin.classes.data-table', [
            'gameClasses' => $this->fetchClasses()
        ]);
    }
}
