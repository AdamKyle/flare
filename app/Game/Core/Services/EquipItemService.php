<?php

namespace App\Game\Core\Services;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use App\Flare\Models\Item;
use App\Flare\Models\Character;
use App\Flare\Events\UpdateTopBarEvent;
use App\Game\Core\Comparison\ItemComparison;
use App\Game\Core\Exceptions\EquipItemException;

class EquipItemService {

    /**
     * @var Request $request
     */
    private $request;

    /**
     * @var Character $character
     */
    private $character;

    /**
     * Set the request
     *
     * @param Request $request
     * @return EquipItemService
     */
    public function setRequest(Request $request): EquipItemService {
        $this->request = $request;

        return $this;
    }

    /**
     * Set the character
     *
     * @param Charactr $character
     * @return EquipItemService
     */
    public function setCharacter(Character $character): EquipItemService {
        $this->character = $character;

        return $this;
    }

    /**
     * Equip the item
     *
     * @return Item
     */
    public function equipItem(): Item {

        $characterSlot = $this->character->inventory->slots->filter(function($slot) {
            return $slot->id === (int) $this->request->slot_id && !$slot->equipped;
        })->first();

        if (is_null($characterSlot)) {
            throw new EquipItemException('Could not equip item because you either do not have it, or it is equipped already.');
        }

        $itemForPosition = $this->character->inventory->slots->filter(function($slot) {
            return $slot->position === $this->request->position;
        })->first();

        if (!is_null($itemForPosition)) {
            $itemForPosition->update(['equipped' => false]);
        }

        $characterSlot->update([
            'equipped' => true,
            'position' => $this->request->position,
        ]);

        event(new UpdateTopBarEvent($this->character));

        return $characterSlot->item;
    }

    /**
     * Get Item stats
     *
     * @param Item $toCompare
     * @param Colection $inventorySlots
     * @return array
     */
    public function getItemStats(Item $toCompare, Collection $inventorySlots): array {
       return resolve(ItemComparison::class)->fetchDetails($toCompare, $inventorySlots);
    }
}
