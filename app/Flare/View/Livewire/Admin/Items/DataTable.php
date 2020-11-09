<?php

namespace App\Flare\View\Livewire\Admin\Items;

use Livewire\Component;
use Livewire\WithPagination;
use App\Flare\Models\Item;
use App\Flare\View\Livewire\Core\DataTables\WithSorting;

class DataTable extends Component
{
    use WithPagination, WithSorting;
    
    public $affixId = null;
    
    public $search      = '';
    public $sortField   = 'type';
    public $perPage     = 10;
    public $selected    = [];

    protected $paginationTheme = 'bootstrap';

    public function fetchItems() {
        if (auth()->user()->hasRole('Admin')) {
            $items = Item::dataTableSearch($this->search);

            if (!is_null($this->affixId)) {
                $items = $items->where('item_suffix_id', $this->affixId)
                               ->orWhere('item_prefix_id', $this->affixId);
            }
            
            return $items->orderBy($this->sortField, $this->sortBy)
                         ->paginate($this->perPage);
        }

        return Item::dataTableSearch($this->search)
                       ->where('type', '!=', 'quest')
                       ->where('item_suffix_id', null)
                       ->where('item_prefix_id', null)
                       ->orderBy($this->sortField, $this->sortBy)
                       ->paginate($this->perPage);
    }

    public function render()
    {
        return view('components.livewire.admin.items.data-table', [
            'items' => $this->fetchItems(),
        ]);
    }
}
