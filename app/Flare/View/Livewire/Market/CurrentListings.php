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
        $marketBoard = MarketBoard::where('character_id', $this->character->id)->join('characters', function($join) {
            $join->on('characters.id', '=', 'market_board.character_id');
        })->join('items', function($join) {
            $join = $join->on('items.id', '=', 'market_board.item_id');

            if (!empty($this->search)) {
                $join->where('items.name', 'like', '%'.$this->search.'%');
            }

            return $join;
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
