<?php

namespace App\Flare\View\Livewire\Admin\Kingdoms\Units;

use App\Flare\Models\GameUnit;
use App\Flare\View\Livewire\Core\DataTables\WithSorting;
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

    public function fetch() {
        return GameUnit::dataTableSearch($this->search)->orderBy($this->sortField, $this->sortBy)->paginate($this->perPage);
    }

    public function render()
    {
        return view('components.livewire.admin.kingdoms.units.data-table', [
            'units' => $this->fetch(),
        ]);
    }
}
