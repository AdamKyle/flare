<?php

namespace App\Flare\View\Livewire\Character\Inventory;

use Livewire\Component;
use Livewire\WithPagination;
use App\Flare\View\Livewire\Core\DataTables\WithSorting;
use App\Flare\View\Livewire\Core\DataTables\WithSelectAll;

class DataTable extends Component
{
    use WithPagination, WithSorting, WithSelectAll;

    public $search                   = '';

    public $sortField                = 'items.type';

    public $perPage                  = 10;

    protected $paginationTheme       = 'bootstrap';

    public $includeEquipped          = false;

    public $includeQuestItems        = false;

    public $allowUnequipAll          = false;

    public $allowInventoryManagement = false;

    public $batchSell                = false;

    public $marketBoard              = false;

    public $craftOnly                = false;

    public $allowMassDestroy         = false;

    public $totalGoldDust            = 0;

    public $character;

    public function getDataQueryProperty() {
        if ($this->search !== '') {
            $this->page = 1;
        }

        if (!is_null($this->character)) {
            $character = $this->character;
        }

        $slots = $character->inventory->slots()->join('items', function($join) {
            $join = $join->on('inventory_slots.item_id', '=', 'items.id');

            if (!$this->includeQuestItems) {
                $join->where('items.type', '!=', 'quest');
            }

            if ($this->batchSell) {
                $join->whereNull('items.item_prefix_id')->whereNull('items.item_suffix_id');
                $join->where('items.craft_only', $this->craftOnly);
            }

            if ($this->marketBoard) {
                $join->where('items.market_sellable', true);
            }

            if ($this->craftOnly) {
                $join->where('items.craft_only', $this->craftOnly);
            }

            return $join;
        });

        return $slots->select('inventory_slots.*')
              ->where('equipped', $this->includeEquipped)
              ->orderBy($this->sortField, $this->sortBy);
    }

    public function getDataProperty() {
        $slots = $this->dataQuery->get();

        if ($this->search !== '') {
            return collect($slots->filter(function ($slot) {
                return str_contains($slot->item->affix_name, $this->search) || str_contains($slot->item->name, $this->search);
            })->all())->paginate($this->perPage);
        }

        return $slots->paginate($this->perPage);
    }

    public function fetchSlots() {
        return $this->data;
    }

    public function destroyAllItems(string $type = null) {
        if ($type === 'disenchant') {
            $this->character->inventory->slots->filter(function($slot) {
                if (!$slot->equipped && $slot->item->type !== 'quest') {
                    if (!is_null($slot->item->item_prefix_id) || !is_null($slot->item->item_suffix_id)) {
                        $goldDust = rand(1, 150);

                        $this->character->update([
                            'gold_dust' => $this->character->gold_dust + $goldDust,
                        ]);

                        $this->character = $this->character->refresh();
                        $this->totalGoldDust += $goldDust;
                    }

                    $slot->delete();
                }
            });
        } else {
            $this->character->inventory->slots->filter(function($slot) {
                if (!$slot->equipped && $slot->item->type !== 'quest') {
                    if (!is_null($slot->item->item_prefix_id) || !is_null($slot->item->item_suffix_id)) {
                        $goldDust = rand(1, 25);


                        $this->character->update([
                            'gold_dust' => $this->character->gold_dust + $goldDust,
                        ]);

                        $this->character = $this->character->refresh();
                        $this->totalGoldDust += $goldDust;
                    }

                    $slot->delete();
                }
            });
        }

        $this->resetSelect();

        session()->flash('success', 'You gained: '.$this->totalGoldDust.' Gold Dust from all items destroyed.');

        return redirect()->to(route('game.character.sheet'));
    }

    public function render()
    {
        $this->selectAllRenderHook();

        return view('components.livewire.character.inventory.data-table', [
            'slots' => $this->fetchSlots(),
        ]);
    }
}
