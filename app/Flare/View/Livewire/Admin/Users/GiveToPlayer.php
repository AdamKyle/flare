<?php

namespace App\Flare\View\Livewire\Admin\Users;

use App\Admin\Services\GiveToPlayerService;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use Illuminate\Support\Collection;
use Livewire\Component;

class GiveToPlayer extends Component
{
    private const CURRENCY_GRANT_TYPES = [
        'gold',
        'gold_dust',
        'shards',
        'copper_coins',
    ];

    public Character $character;

    public string $grantType = 'item';

    public string $currency = 'gold';

    public int $amount = 1;

    public string $itemSearch = '';

    public ?int $selectedItemId = null;

    public ?string $selectedItemName = null;

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function mount(Character $character): void
    {
        $this->character = $character;
    }

    public function updatedGrantType(): void
    {
        $this->clearMessages();
        $this->clearItemSelection();
        $this->amount = 1;

        if (in_array($this->grantType, self::CURRENCY_GRANT_TYPES, true)) {
            $this->currency = $this->grantType;
        }
    }

    public function selectItem(int $itemId): void
    {
        $item = Item::find($itemId);

        if (is_null($item)) {
            $this->errorMessage = 'The selected item could not be found.';

            return;
        }

        $this->selectedItemId = $item->id;
        $this->selectedItemName = $item->name;
        $this->itemSearch = $item->name;
        $this->clearMessages();
    }

    public function give(GiveToPlayerService $giveToPlayerService): void
    {
        $this->clearMessages();
        $this->validate($this->rules());

        if ($this->grantType === 'item') {
            $item = Item::find($this->selectedItemId);

            if (is_null($item)) {
                $this->errorMessage = 'Select an item before giving it to the player.';

                return;
            }

            $giveToPlayerService->giveItem($this->character, $item);
            $this->successMessage = $item->name.' was given to '.$this->character->name.'.';
            $this->clearItemSelection();

            return;
        }

        if ($this->grantType === 'gold_bars') {
            $giveToPlayerService->giveGoldBars($this->character, $this->amount);
            $this->successMessage = 'Gold bars were given to all kingdoms owned by '.$this->character->name.'.';
            $this->amount = 1;

            return;
        }

        $giveToPlayerService->giveCurrency($this->character, $this->grantType, $this->amount);
        $this->successMessage = ucfirst(str_replace('_', ' ', $this->grantType)).' was given to '.$this->character->name.'.';
        $this->amount = 1;
    }

    public function getSearchResultsProperty(): Collection
    {
        if (strlen($this->itemSearch) < 2 || $this->grantType !== 'item') {
            return collect();
        }

        return Item::where('name', 'like', '%'.$this->itemSearch.'%')
            ->orderBy('name')
            ->limit(10)
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.users.give-to-player');
    }

    private function rules(): array
    {
        return [
            'grantType' => 'required|in:item,gold,gold_dust,shards,copper_coins,gold_bars',
            'currency' => 'required|in:gold,gold_dust,shards,copper_coins',
            'amount' => 'required|integer|min:1',
            'selectedItemId' => $this->grantType === 'item' ? 'required|integer|exists:items,id' : 'nullable',
        ];
    }

    private function clearItemSelection(): void
    {
        $this->itemSearch = '';
        $this->selectedItemId = null;
        $this->selectedItemName = null;
    }

    private function clearMessages(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;
    }
}
