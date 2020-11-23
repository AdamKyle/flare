<?php

namespace App\Flare\View\Livewire\Admin\Items;

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

    protected $paginationTheme = 'bootstrap';

    public function getDataProperty() {
        return $this->dataQuery->paginate($this->perPage);
    }

    public function getDataQueryProperty() {
        if (is_null(auth()->user())) {
            
            $item = Item::dataTableSearch($this->search);
      
            if ($this->only === 'quest-items-book') {
                $item = $item->where('type', 'quest')->where('name', 'like', '%Book%');
            } else {
                $item = $item->where('type', '!=', 'quest');
            }
            
            return $item->where('item_suffix_id', null)
                        ->where('item_prefix_id', null)
                        ->orderBy($this->sortField, $this->sortBy);
        }
        
        if (auth()->user()->hasRole('Admin')) {
            $items = Item::dataTableSearch($this->search);

            if (!is_null($this->affixId)) {
                $items = $items->where('item_suffix_id', $this->affixId)
                               ->orWhere('item_prefix_id', $this->affixId);
            }
            
            return $items->orderBy($this->sortField, $this->sortBy);
        }

        return Item::dataTableSearch($this->search)
                       ->where('type', '!=', 'quest')
                       ->where('item_suffix_id', null)
                       ->where('item_prefix_id', null)
                       ->orderBy($this->sortField, $this->sortBy);
    }

    

    public function fetchItems() {
        return $this->data;
    }

    public function render()
    {

        $this->selectAllRenderHook();

        return view('components.livewire.admin.items.data-table', [
            'items' => $this->fetchItems(),
        ]);
    }
}
