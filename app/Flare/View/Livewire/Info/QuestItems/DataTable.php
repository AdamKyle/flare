<?php

namespace App\Flare\View\Livewire\Info\QuestItems;

use Livewire\Component;
use Livewire\WithPagination;
use App\Flare\Models\Item;
use App\Flare\View\Livewire\Core\DataTables\WithSorting;
use App\Flare\View\Livewire\Core\DataTables\WithSelectAll;

class DataTable extends Component {
    use WithPagination, WithSorting, WithSelectAll;

    public $search                = '';
    public $sortField             = 'name';
    public $perPage               = 10;

    protected $paginationTheme = 'bootstrap';

    public function getDataProperty() {
        return $this->dataQuery->paginate($this->perPage);
    }

    public function getDataQueryProperty() {
        if ($this->search !== '') {
            $this->page = 1;
        }

        $items = Item::dataTableSearch($this->search)->where('type', 'quest');


        return $items->orderBy($this->sortField, $this->sortBy);
    }

    public function fetchItems() {
        return $this->data;
    }

    public function render()
    {
        return view('components.livewire.info.quest-items.data-table', [
            'items' => $this->fetchItems(),
        ]);
    }
}
