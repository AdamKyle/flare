<?php

namespace App\Flare\View\Livewire\Admin\CharacterModeling\ItemAssignment;

use Livewire\Component;
use Livewire\WithPagination;
use App\Flare\Models\Item;
use App\Flare\View\Livewire\Core\DataTables\WithSorting;
use App\Flare\View\Livewire\Core\DataTables\WithSelectAll;

class DataTable extends Component
{
    use WithPagination, WithSorting, WithSelectAll;

    public $affixId = null;

    public $search       = '';
    public $sortField    = 'type';
    public $perPage      = 10;
    public $only         = null;
    public $character    = null;

    protected $paginationTheme = 'bootstrap';

    public function getDataProperty() {
        return $this->dataQuery->paginate($this->perPage);
    }

    public function getDataQueryProperty() {
        if ($this->search !== '') {
            $this->page = 1;
        }

        return Item::dataTableSearch($this->search)->where('type', '!=', 'quest')->orderBy($this->sortField, $this->sortBy);
    }

    public function fetchItems() {
        return $this->data;
    }

    public function render()
    {

        $this->selectAllRenderHook();

        return view('components.livewire.admin.character-modeling.item-assignment.data-table', [
            'items' => $this->fetchItems(),
        ]);
    }
}
