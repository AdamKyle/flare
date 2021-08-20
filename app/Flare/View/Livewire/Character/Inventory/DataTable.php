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

    public $inventorySetEquipped     = false;

    public $hasEmptyInventorySets    = false;

    public $onlyQuestItems           = false;

    public $onlyUsable               = false;

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

            if (!$this->includeQuestItems && !$this->onlyQuestItems && !$this->onlyUsable) {
                $join->whereNotIn('items.type', ['quest', 'alchemy']);
            }

            if ($this->batchSell) {
                $join->whereNull('items.item_prefix_id')->whereNull('items.item_suffix_id');
                $join->where('items.craft_only', $this->craftOnly);
                $join->where('items.usable', false);
            }

            if ($this->onlyQuestItems) {
                $join->where('items.type', 'quest');
            }

            if ($this->onlyUsable) {
                $join->where('items.usable', true)->where('items.type', 'alchemy');
            }

            if ($this->marketBoard) {
                $join->where('items.market_sellable', true);
            }

            if ($this->craftOnly) {
                $join->where('items.craft_only', $this->craftOnly);
            }

            return $join;
        })->select('inventory_slots.*');

        if ($this->onlyQuestItems) {
            return $slots->orderBy($this->sortField, $this->sortBy);
        }

        if ($slots->where('equipped', $this->includeEquipped)->get()->isEmpty() && $this->includeEquipped) {
            $equippedInventorySet = $character->inventorySets->where('is_equipped', true)->first();

            if (!is_null($equippedInventorySet)) {
                $slots = $character->inventorySets->where('is_equipped', true)->first()->slots()->join('items', function($join) {
                    return $join->on('set_slots.item_id', '=', 'items.id');
                })->select('set_slots.*');

                $this->inventorySetEquipped  = true;
            }
        }

        $this->hasEmptyInventorySets = $character->inventorySets()->doesntHave('slots')->get()->isNotEmpty();

        return $slots
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

    public function useAllSelectedItems(UseItemService $useItemService) {
        if ($this->character->boons->count() === 10) {
            session()->flash('error', 'You can only have a max of ten boons applied. Check active boons to see which ones you have. You can always cancel one by clicking on the row.');

            return redirect()->route('game.character.sheet');
        }

        $slots = $this->character->inventory->slots()->findMany($this->selected);

        if ($slots->isEmpty()) {
            session()->flash('error', 'No slots found with these items.');

            return redirect()->route('game.character.sheet');
        }

        foreach ($slots as $slot) {
            $useItemService->useItem($slot, $this->character, $slot->item);
        }

        session()->flash('success', 'Used selected items. Check your Active Boons section.');

        return redirect()->route('game.character.sheet');
    }

    public function destroyAllItems(DisenchantService $disenchantService, string $type = null) {
        $this->totalGoldDust = 0;

        if ($type === 'disenchant') {
            $this->character->inventory->slots->filter(function($slot) use ($disenchantService) {
                if (!$slot->equipped && $slot->item->type !== 'quest') {
                    if (!is_null($slot->item->item_prefix_id) || !is_null($slot->item->item_suffix_id)) {
                        $disenchantService->disenchantWithSkill($this->character, $slot);
                        $this->totalGoldDust = $disenchantService->getGoldDust();
                    }
                }
            });
        } else {
            $this->character->inventory->slots->filter(function($slot) use ($disenchantService) {
                if (!$slot->equipped && $slot->item->type !== 'quest') {
                    if (!is_null($slot->item->item_prefix_id) || !is_null($slot->item->item_suffix_id)) {
                        $disenchantService->disenchantWithOutSkill($this->character, $slot);

                        $this->totalGoldDust = $disenchantService->getGoldDust();
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
