<?php

namespace App\Flare\View\Livewire\Admin\Kingdoms\Buildings;

use App\Flare\Models\GameKingdomBuilding;
use Livewire\Component;
use Livewire\WithPagination;
use App\Flare\View\Livewire\Core\DataTables\WithSorting;

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
        return GameKingdomBuilding::dataTableSearch($this->search)->orderBy($this->sortField, $this->sortBy)->paginate($this->perPage);
    }

    public function render() {
        return view('components.livewire.admin.kingdoms.buildings.data-table', [
            'buildings' => $this->fetch(),
        ]);
    }
}
