<?php

namespace App\Game\Core\Services;

use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySet;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\SetSlot;
use App\Game\Core\Events\UpdateAttackStats;
use League\Fractal\Manager;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use League\Fractal\Resource\Item as ResourceItem;
use App\Flare\Models\Item;
use App\Flare\Models\Character;
use App\Game\Core\Events\UpdateTopBarEvent;
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
     * @var Character $character
     */
    private Character $character;

    /**
     * @var array $request
     */
    private array $request;

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
     * @param array $request
     * @return EquipItemService
     */
    public function setRequest(array $request): EquipItemService {
        $this->request = $request;

        return $this;
    }

    /**
     * Set the character
     *
     * @param Character $character
     * @return EquipItemService
     */
    public function setCharacter(Character $character): EquipItemService {
        $this->character = $character;

        return $this;
    }

    public function replaceItem(): Item {
        $characterSlot = $this->character->inventory->slots->filter(function($slot) {
            return $slot->id === (int) $this->request['slot_id'] && !$slot->equipped;
        })->first();

        if (is_null($characterSlot)) {
            throw new EquipItemException('The item you are trying to equip as a replacement, does not exist.');
        }

        $equippedSet = $this->character->inventorySets()->where('is_equipped', true)->first();

        if (!is_null($equippedSet)) {
            if ($this->character->isInventoryFull()) {
                throw new EquipItemException('Inventory is full. Cannot replace a set item. Please make some room.');
            }

            $uniqueSlot          = $this->getUniqueFromSet($equippedSet);
            $isItemToEquipUnique = $this->isItemToEquipUnique($characterSlot->item);
            $isItemToReplaceUnique = $this->isItemToBeReplacedUnique($equippedSet);

            if (!is_null($uniqueSlot) && $isItemToEquipUnique && !$isItemToReplaceUnique) {
                throw new EquipItemException('Cannot equip another unique.');
            }

            $this->unequipSlot($characterSlot, $equippedSet);

            $equippedSet->slots()->create([
                'inventory_set_id' => $equippedSet->id,
                'item_id'  => $characterSlot->item->id,
                'equipped' => true,
                'position' => $this->request['position'],
            ]);

            $characterSlot->delete();
        } else {
            $uniqueSlot          = $this->getUniqueFromSet($this->character->inventory);
            $isItemToEquipUnique = $this->isItemToEquipUnique($characterSlot->item);
            $isItemToReplaceUnique = $this->isItemToBeReplacedUnique($this->character->inventory);

            if (!is_null($uniqueSlot) && $isItemToEquipUnique && !$isItemToReplaceUnique) {
                throw new EquipItemException('Cannot equip another unique.');
            }

            $this->unequipSlot($characterSlot, $this->character->inventory);

            $characterSlot->update([
                'equipped' => true,
                'position' => $this->request['position'],
            ]);
        }

        $character = $this->character->refresh();

        event(new UpdateTopBarEvent($character));

        return $characterSlot->item;
    }

    public function getUniqueFromSet(Inventory|InventorySet $equipped): InventorySlot|SetSlot|null {
        return $equipped->slots->filter(function($slot) {
            if (!is_null($slot->item->item_prefix_id)) {
                return $slot->item->itemPrefix->randomly_generated && $slot->equipped;
            }

            if (!is_null($slot->item->item_suffix_id)) {
                return $slot->item->itemSuffix->randomly_generated && $slot->equipped;
            }
        })->first();
    }

    public function isItemToEquipUnique(Item $item): bool {
        if (!is_null($item->item_prefix_id)) {
            return $item->itemPrefix->randomly_generated;
        }

        if (!is_null($item->item_suffix_id)) {
            return $item->itemSuffix->randomly_generated;
        }

        return false;
    }

    public function isItemToBeReplacedUnique(Inventory|InventorySet $inventory): bool {
        $item = $inventory->slots->filter(function($slot) {
            return $slot->position === $this->request['position'] && $slot->equipped;
        })->first();

        if (is_null($item)) {
            return false;
        }

        $item = $item->item;

        if (!is_null($item->item_prefix_id)) {
            return $item->itemPrefix->randomly_generated;
        }

        if (!is_null($item->item_suffix_id)) {
            return $item->itemSuffix->randomly_generated;
        }

        return false;
    }

    /**
     * Get Item stats
     *
     * @param Item $toCompare
     * @param Collection $inventorySlots
     * @param Character $character
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
     * @param string $type
     * @return bool
     */
    public function isTwoHandedItemEquipped(Item $itemToEquip, Collection $inventorySlots, string $type): bool {
        $validTypes = ['bow', 'hammer', 'stave'];

        if (!in_array($itemToEquip->type, $validTypes)) {
             return false;
        }

        return $inventorySlots->filter(function($slot) use($type) {
            return $slot->item->type === $type && $slot->equipped;
        })->isNotEmpty();
    }

    public function unequipSlot(InventorySlot $characterSlot, Inventory|InventorySet $inventory) {
        if ($characterSlot->item->type === 'bow') {
            $this->unequipBothHands();
        } else if ($characterSlot->item->type === 'hammer') {
            $this->unequipBothHands();
        } else if ($characterSlot->item->type === 'stave') {
            $this->unequipBothHands();
        } else {

            if (!$this->removeTwoHandedWeapon($inventory)) {
                $itemForPosition = $inventory->slots->filter(function ($slot) {
                    return $slot->position === $this->request['position'] && $slot->equipped;
                })->first();

                if (!is_null($itemForPosition)) {
                    $itemForPosition->update(['equipped' => false]);

                    $this->character->inventory->slots()->create([
                        'inventory_id' => $this->character->inventory->id,
                        'item_id' => $itemForPosition->item->id,
                    ]);

                    $itemForPosition->delete();
                }
            }
        }
    }

    protected function removeTwoHandedWeapon(Inventory|InventorySet $inventory): bool {
        if ($this->request['position'] === 'right-hand' || $this->request['position'] === 'left-hand') {

            $itemsForPosition = $inventory->slots->filter(function($slot) {
                return ($slot->position === 'right-hand' || $slot->position === 'left-hand') && $slot->equipped;
            });

            $unequipTypes = ['bow', 'hammer', 'stave'];

            foreach ($itemsForPosition as $itemForPosition) {
                if (in_array($itemForPosition->item->type, $unequipTypes)) {
                    $itemForPosition->update(['equipped' => false]);

                    $this->character->inventory->slots()->create([
                        'inventory_id' => $this->character->inventory->id,
                        'item_id'      => $itemForPosition->item->id,
                    ]);

                    $itemForPosition->delete();

                    return true;
                }
            }
        }

        return false;
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
