<?php

namespace App\Flare\View\Livewire\Character\Inventory;

use App\Flare\Models\GameSkill;
use App\Game\Core\Services\UseItemService;
use App\Game\Skills\Services\DisenchantService;
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
                $join->whereNotIn('items.type', ['quest', 'alchemy']);
            }

            if ($this->batchSell) {
                $join->whereNull('items.item_prefix_id')->whereNull('items.item_suffix_id');
                $join->where('items.craft_only', $this->craftOnly);
                $join->where('items.usable', false);
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

        $slots->transform(function($slot) {
            $skills = [];

            if ($slot->item->usable && !is_null($slot->item->affects_skill_type)) {
                $skills = GameSkill::where('type', $slot->item->affects_skill_type)->pluck('name')->toArray();
            }

            $slot->affects_skills = $skills;

            return $slot;
        });

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

    public function destroyAllItems(DisenchantService $disenchantService, string $type = null) {

        if ($type === 'disenchant') {
            $this->character->inventory->slots->filter(function($slot) use ($disenchantService) {
                if (!$slot->equipped && $slot->item->type !== 'quest') {
                    if (!is_null($slot->item->item_prefix_id) || !is_null($slot->item->item_suffix_id)) {
                        $disenchantService->disenchantWithSkill($this->character, $slot);

                        $this->totalGoldDust += $disenchantService->getGoldDust();
                    }
                }
            });
        } else {
            $this->character->inventory->slots->filter(function($slot) use ($disenchantService) {
                if (!$slot->equipped && $slot->item->type !== 'quest') {
                    if (!is_null($slot->item->item_prefix_id) || !is_null($slot->item->item_suffix_id)) {
                        $disenchantService->disenchantWithOutSkill();

                        $this->totalGoldDust += $disenchantService->getGoldDust();
                    }

                    $slot->delete();
                }
            });
        }

        $this->resetSelect();

        session()->flash('success', 'You gained: '.$this->totalGoldDust.' Gold Dust from all items destroyed.');

        return redirect()->to(route('game.character.sheet'));
    }

    public function useAllItems(UseItemService $useItemService) {
        $this->character->inventory->slots->filter(function($slot) use ($useItemService) {
            if ($slot->item->usable) {
                $useItemService->useItem($slot, $this->character, $slot->item);
            }
        });

        session()->flash('success', 'Used every single item in your inventory. Check: Active Boons tab');

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
