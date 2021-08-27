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

    public $search                = '';
    public $sortField             = 'cost';
    public $perPage               = 10;
    public $only                  = null;
    public $character             = null;
    public $isHelp                = false;
    public $craftOnly             = false;
    public $type                  = null;
    public $showSkillInfo         = false;
    public $showDropDown          = false;
    public $showAlchemy           = true;
    public $showOtherCurrencyCost = true;


    protected $paginationTheme = 'bootstrap';

    public function getDataProperty() {
        return $this->dataQuery->paginate($this->perPage);
    }

    public function getDataQueryProperty() {
        if ($this->search !== '') {
            $this->page = 1;
        }

        $items = Item::dataTableSearch($this->search);

        if (!is_null($this->only)) {
            if ($this->only === 'quest-items-book') {
                $items = $items->where('name', 'like', '%Book%')
                               ->where('type', 'quest');
            } else {
                $items = $items->where('type', '!=', 'quest');
            }

            return $items->orderBy($this->sortField, $this->sortBy);
        }

        if (auth()->user()) {
            if (auth()->user()->hasRole('Admin')) {
                if (!is_null($this->affixId)) {
                    $items = $items->where('item_suffix_id', $this->affixId)
                        ->orWhere('item_prefix_id', $this->affixId);
                }

                if (!is_null($this->type)) {
                    $items = $items->where('type', $this->type);
                }

                return $items->orderBy($this->sortField, $this->sortBy);
            }
        }

        $items = $items->where('type', '!=', 'quest')
                       ->where('item_suffix_id', null)
                       ->where('item_prefix_id', null)
                       ->where('craft_only', $this->craftOnly);

        if (!is_null($this->type)) {
            $items = $items->where('type', $this->type);
        } else if ($this->craftOnly && $this->type !== 'alchemy') {
            $items = $items->where('type', '!=', 'alchemy');
        }

        $this->showAlchemy = false;

        return $items->orderBy($this->sortField, $this->sortBy);
    }

    public function setType($type) {
        if ($type === 'reset') {
            $this->type = null;
        } else {
            $this->type = $type;
        }
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
