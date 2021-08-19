<?php

namespace App\Game\Core\Services;

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

        $equippedSet = $this->character->inventorySets()->where('is_equipped', true)->first();

        if (!is_null($equippedSet)) {
            $this->inventorySetService->unEquipInventorySet($equippedSet);
        }

        if ($characterSlot->item->type === 'bow') {
            $this->unequipBothHands();
        } else {
            $hasBowEquipped = $this->character->inventory->slots->filter(function($slot) {
                return $slot->item->type === 'bow' && $slot->equipped;
            })->isNotEmpty();


            if ($hasBowEquipped && ($characterSlot->item->type === 'weapon' || $characterSlot->item->type === 'shield')) {
                $this->unequipBothHands();
            } else {
                $itemForPosition = $this->character->inventory->slots->filter(function($slot) {
                    return $slot->position === $this->request->position && $slot->equipped;
                })->first();

                if (!is_null($itemForPosition)) {
                    $itemForPosition->update(['equipped' => false]);
                }
            }
        }

        $characterSlot->update([
            'equipped' => true,
            'position' => $this->request->position,
        ]);

        $character = $this->character->refresh();

        event(new UpdateTopBarEvent($character));

        $characterData = new ResourceItem($character, $this->characterTransformer);
        event(new UpdateAttackStats($this->manager->createData($characterData)->toArray(), $character->user));

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

    public function unequipBothHands() {
        $slots = $this->character->inventory->slots->filter(function($slot) {
            return $slot->equipped;
        });

        foreach ($slots as $slot) {
            if ($slot->position === 'right-hand' || $slot->position === 'left-hand') {
                $slot->update(['equipped' => false]);
            }
        }

        $this->character = $this->character->refresh();
    }
}
