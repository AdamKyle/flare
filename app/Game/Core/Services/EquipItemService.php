<?php

namespace App\Game\Core\Services;

use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySet;
use App\Flare\Models\InventorySlot;
use App\Game\Core\Events\UpdateAttackStats;
use League\Fractal\Manager;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use League\Fractal\Resource\Item as ResourceItem;
use App\Flare\Models\Item;
use App\Flare\Models\Character;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Game\Core\Comparison\ItemComparison;
use App\Game\Core\Exceptions\EquipItemException;


class EquipItemService {

    /**
     * @var Manager $manager
     */
    private $manager;

    /**
     * @var CharacterAttackTransformer $characterTransformer
     */
    private $characterTransformer;

    /**
     * @var InventorySetService $inventorySetService
     */
    private $inventorySetService;

    /**
     * @var Request $request
     */
    private $request;

    /**
     * @var Character $character
     */
    private $character;

    /**
     * EquipItemService constructor.
     *
     * @param Manager $manager
     * @param CharacterAttackTransformer $characterTransformer
     * @param InventorySetService $inventorySetService
     */
    public function __construct(Manager $manager, CharacterAttackTransformer $characterTransformer, InventorySetService $inventorySetService) {
        $this->manager              = $manager;
        $this->characterTransformer = $characterTransformer;
        $this->inventorySetService  = $inventorySetService;
    }

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

    public function replaceItem(): Item {
        $characterSlot = $this->character->inventory->slots->filter(function($slot) {
            return $slot->id === (int) $this->request->slot_id && !$slot->equipped;
        })->first();

        if (is_null($characterSlot)) {
            throw new EquipItemException('The item you are trying to equip as a replacement, does not exist.');
        }

        $equippedSet = $this->character->inventorySets()->where('is_equipped', true)->first();

        if (!is_null($equippedSet)) {
            if ($this->character->isInventoryFull()) {

                throw new EquipItemException('Inventory is full. Cannot replace a set item. Please make some room.');
            }


            $this->unequipSlot($characterSlot, $equippedSet);

            $equippedSet->slots()->create([
                'inventory_set_id' => $equippedSet->id,
                'item_id'  => $characterSlot->item->id,
                'equipped' => true,
                'position' => $this->request->position,
            ]);

            $characterSlot->delete();
        } else {

            $this->unequipSlot($characterSlot, $this->character->inventory);

            $characterSlot->update([
                'equipped' => true,
                'position' => $this->request->position,
            ]);
        }

        $character = $this->character->refresh();

        event(new UpdateTopBarEvent($character));

        return $characterSlot->item;
    }

    /**
     * Get Item stats
     *
     * @param Item $toCompare
     * @param Collection $inventorySlots
     * @return array
     */
    public function getItemStats(Item $toCompare, Collection $inventorySlots, Character $character): array {
       return resolve(ItemComparison::class)->fetchDetails($toCompare, $inventorySlots, $character);
    }

    /**
     * Do we have a bow equipped?
     *
     * @param Item $itemToEquip
     * @param Collection $inventorySlots
     * @return bool
     */
    public function isBowEquipped(Item $itemToEquip, Collection $inventorySlots): bool {
        $validTypes = ['weapon', 'shield', 'bow'];

        if (!in_array($itemToEquip->type, $validTypes)) {
             return false;
        }

        return $inventorySlots->filter(function($slot) {
            return $slot->item->type === 'bow' && $slot->equipped;
        })->isNotEmpty();
    }

    public function unequipSlot(InventorySlot $characterSlot, Inventory|InventorySet $inventory) {
        if ($characterSlot->item->type === 'bow') {
            $this->unequipBothHands();
        } else {
            if ($this->hasBowEquipped($inventory)) {
                $this->unequipBothHands();
            }

            $itemForPosition = $inventory->slots->filter(function($slot) {
                return $slot->position === $this->request->position && $slot->equipped;
            })->first();

            if (!is_null($itemForPosition)) {
                $itemForPosition->update(['equipped' => false]);

                $this->character->inventory->slots()->create([
                    'inventory_id' => $this->character->inventory->id,
                    'item_id'      => $itemForPosition->item->id,
                ]);

                $itemForPosition->delete();
            }
        }
    }

    /**
     * Check both hands to see if we have a bow equipped.
     *
     * @param Inventory|InventorySet $inventory
     * @return bool
     */
    public function hasBowEquipped(Inventory|InventorySet $inventory): bool {
        $position  = $this->request->position;
        $bowInHand = null;

        if ($position === 'left-hand' || $position === 'right-hand') {
            $bowInHand = $inventory->slots->filter(function($slot) {
                return $slot->equipped && $slot->item->type === 'bow';
            })->first();
        }

        return !is_null($bowInHand);
    }

    /**
     * Unequips both hands.
     */
    public function unequipBothHands() {
        $slots = $this->character->inventory->slots->filter(function($slot) {
            return $slot->equipped;
        });

        $removedFromSet = false;

        if ($slots->isEmpty()) {
            $equippedSet = $this->character->inventorySets()->where('is_equipped', true)->first();

            if (!is_null($equippedSet)) {
                $slots = $equippedSet->slots->filter(function($slot) {
                    return $slot->equipped;
                });

                $removedFromSet = true;
            }
        }

        foreach ($slots as $slot) {
            if ($slot->position === 'right-hand' || $slot->position === 'left-hand') {
                $slot->update(['equipped' => false]);

                if ($removedFromSet) {
                    $this->character->inventory->slots()->create([
                        'inventory_id' => $this->character->inventory->id,
                        'item_id'      => $slot->item->id,
                    ]);

                    $slot->delete();
                }
            }
        }

        $this->character = $this->character->refresh();
    }
}
