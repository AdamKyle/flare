<?php

namespace App\Game\NpcActions\LabyrinthOracle\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Messages\Events\ServerMessageEvent;
use Facades\App\Game\Core\Handlers\DuplicateItemHandler;
use App\Game\Core\Traits\ResponseBuilder;

class ItemTransferService {

    use ResponseBuilder;

    /**
     * @var Item $itemToTransferFromDuplicated
     */
    private Item $itemToTransferFromDuplicated;

    /**
     * @var Item $itemToTransferToDuplicated
     */
    private Item $itemToTransferToDuplicated;

    /**
     * Transfer the enhancements from one item to another.
     *
     * @param Character $character
     * @param array $currencyCosts
     * @param int $itemIdToTransferFrom
     * @param int $itemIdToTransferTo
     * @return array
     */
    public function transferItemEnhancements(Character $character, array $currencyCosts, int $itemIdToTransferFrom, int $itemIdToTransferTo): array {

        if (!$this->canAfford($character, $currencyCosts)) {
            return $this->errorResult('You cannot afford to do this.');
        }

        $itemSlotToTransferFrom = $character->inventory->slots->where('item_id', $itemIdToTransferFrom)->first();
        $itemSlotToTransferTo = $character->inventory->slots->where('item_id', $itemIdToTransferTo)->first();

        if (is_null($itemSlotToTransferFrom) || is_null($itemIdToTransferTo)) {
            return $this->errorResult('You do not have one of these items.');
        }

        $this->itemToTransferFromDuplicated = DuplicateItemHandler::duplicateItem($itemSlotToTransferFrom->item);
        $this->itemToTransferToDuplicated = DuplicateItemHandler::duplicateItem($itemSlotToTransferTo->item);

        $this->moveAffixesOver();

        DuplicateItemHandler::applyHolyStacks($this->itemToTransferFromDuplicated, $this->itemToTransferToDuplicated);

        $this->itemToTransferToDuplicated = $this->itemToTransferToDuplicated->refresh();
        $this->itemToTransferFromDuplicated = $this->itemToTransferFromDuplicated->refresh();

        DuplicateItemHandler::applyGems($this->itemToTransferFromDuplicated, $this->itemToTransferToDuplicated);

        $this->itemToTransferToDuplicated = $this->itemToTransferToDuplicated->refresh();
        $this->itemToTransferFromDuplicated = $this->itemToTransferFromDuplicated->refresh();

        $itemSlotToTransferFrom->update([
            'item_id' => $this->itemToTransferFromDuplicated->id,
        ]);

        $itemSlotToTransferTo->update([
            'item_id' => $this->itemToTransferToDuplicated->id,
        ]);

        $itemSlotToTransferTo = $itemIdToTransferTo->refresh();

        event(new ServerMessageEvent($character->user, 'The Labyrinth Oracle works his magic to transfer the magical enhancements to: ' . $this->itemToTransferToDuplicated->affix_name, $itemSlotToTransferTo->id));

        return $this->successResult([
            'message' => 'Transferred attributes (Enchantments, Holy Oils and Gems) from: ' . $this->itemToTransferFromDuplicated->affix_name . ' To: ' . $this->itemToTransferToDuplicated->affix_name,
            'inventory' => $character->refresh()->inventory->slots->pluck('item.affix_name', 'item.id')->toArray(),
        ]);
    }

    /**
     * Can the character afford this?
     *
     * @param Character $character
     * @param array $cost
     * @return bool
     */
    protected function canAfford(Character $character, array $cost): bool {
        foreach ($cost as $currencyName => $cost) {
            if ($character->{$currencyName} < $cost) {
                return false;
            }
        }

        return true;
    }

    /**
     * Move the affixes over.
     *
     * @return void
     */
    protected function moveAffixesOver(): void {
        $this->itemToTransferToDuplicated->update([
            'item_prefix_id' => $this->itemToTransferFromDuplicated->item_prefix_id,
            'item_suffix_id' => $this->itemToTransferFromDuplicated->item_suffix_id,
        ]);

        $this->itemToTransferFromDuplicated->update([
            'item_prefix_id' => null,
            'item_suffix_id' => null,
        ]);

        $this->itemToTransferToDuplicated = $this->itemToTransferToDuplicated->refresh();
        $this->itemToTransferFromDuplicated = $this->itemToTransferFromDuplicated->refresh();
    }
}
