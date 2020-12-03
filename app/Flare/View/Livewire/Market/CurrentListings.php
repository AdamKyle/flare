<?php

namespace App\Flare\View\Livewire\Market;

use App\Flare\Models\MarketBoard;
use App\Flare\View\Livewire\Core\DataTables\WithSorting;
use Livewire\Component;
use Livewire\WithPagination;

class CurrentListings extends Component
{
    use WithPagination, WithSorting;

    public $search             = '';

    public $sortField          = 'listed_price';

    public $perPage            = 10;

    public $character          = null;

    protected $paginationTheme = 'bootstrap';
    
    public function getDataQueryProperty() {
        $marketBoard = MarketBoard::join('characters', function($join) {
            $join->on('characters.id', '=', 'market_board.character_id');
        })->join('items', function($join) {
            $join->on('items.id', '=', 'market_board.item_id');
        })->select('market_board.*');
        
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
        return view('components.livewire.market.current-listings', [
            'items' => $this->fetchData(),
        ]);
    }
}
