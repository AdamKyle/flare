<?php

namespace App\Flare\View\Livewire\Market;

use App\Flare\Models\MarketBoard;
use Livewire\Component;
use Livewire\WithPagination;
use App\Flare\View\Livewire\Core\DataTables\WithSorting;
use App\Flare\View\Livewire\Core\DataTables\WithSelectAll;

class ItemBoard extends Component
{
    use WithPagination, WithSorting, WithSelectAll;

    public $search             = '';

    public $sortField          = 'listed_price';

    public $perPage            = 10;

    public $itemId             = null;

    protected $paginationTheme = 'bootstrap';
    

    public function getDataQueryProperty() {
        $marketBoard = MarketBoard::where('item_id', $this->itemId);
        
        return $marketBoard->orderBy($this->sortField, $this->sortBy);
    }

    public function getDataProperty() {
        return $this->dataQuery->paginate($this->perPage);
    }

    public function fetchData() {
        return $this->data;
    }

    public function render()
    {
        $this->selectAllRenderHook();
        
        return view('components.livewire.market.item-board', [
            'items' => $this->fetchData(),
        ]);
    }
}
