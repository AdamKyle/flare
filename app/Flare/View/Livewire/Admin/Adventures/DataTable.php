<?php

namespace App\Flare\View\Livewire\Admin\Adventures;

use Livewire\Component;
use Livewire\WithPagination;
use App\Flare\Models\Adventure;
use App\Flare\View\Livewire\Core\DataTables\WithSorting;

class DataTable extends Component
{
    use WithPagination, WithSorting;

    public $adventureId = null;
    public $search      = '';
    public $sortField   = 'name';
    public $perPage     = 10;

    protected $paginationTheme = 'bootstrap';

    public function render()
    {
        return view('components.livewire.admin.adventures.data-table', [
            'adventures' => Adventure::dataTableSearch($this->search)
                                 ->orderBy($this->sortField, $this->sortBy)
                                 ->paginate($this->perPage),
        ]);
    }
}
