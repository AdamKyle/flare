<?php

namespace App\Game\Core\Services;

use App\Flare\Models\Item;
use Cache;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Game\Core\Values\ValidEquipPositionsValue;

class ComparisonService {

    private $validEquipPositionsValue;

    private $characterInventoryService;

    private $equipItemService;

    public function __construct(
        ValidEquipPositionsValue $validEquipPositionsValue,
        CharacterInventoryService $characterInventoryService,
        EquipItemService $equipItemService)
    {
        $this->validEquipPositionsValue  = $validEquipPositionsValue;
        $this->characterInventoryService = $characterInventoryService;
        $this->equipItemService          = $equipItemService;
    }

    /**
     * @param Character $character
     * @param InventorySlot $itemToEquip
     * @param string|null $type
     */
    public function buildComparisonData(Character $character, InventorySlot $itemToEquip, string $type = null) {
        $service = $this->characterInventoryService->setCharacter($character)
            ->setInventorySlot($itemToEquip)
            ->setPositions($this->validEquipPositionsValue->getPositions($itemToEquip->item))
            ->setInventory($type);

        $viewData = [
            'details'     => [],
            'itemToEquip' => $itemToEquip->item,
            'type'        => $service->getType($itemToEquip->item, $type),
            'slotId'      => $itemToEquip->id,
            'characterId' => $character->id,
            'bowEquipped' => false,
            'setEquipped' => false,
            'hammerEquipped' => false,
            'setIndex'    => 0,
        ];

        if ($service->inventory()->isNotEmpty()) {
            $setEquipped = $character->inventorySets()->where('is_equipped', true)->first();


            $hasSet   = !is_null($setEquipped);
            $setIndex = !is_null($setEquipped) ? $character->inventorySets->search(function($set) {return $set->is_equipped; }) + 1 : 0;

            $viewData = [
                'details'      => $this->equipItemService->getItemStats($itemToEquip->item, $service->inventory(), $character),
                'itemToEquip'  => $itemToEquip->item,
                'type'         => $service->getType($itemToEquip->item, $type),
                'slotId'       => $itemToEquip->id,
                'slotPosition' => $itemToEquip->position,
                'characterId'  => $character->id,
                'bowEquipped'  => $this->equipItemService->isTwoHandedItemEquipped($itemToEquip->item, $service->inventory(), 'bow'),
                'hammerEquipped' => $this->equipItemService->isTwoHandedItemEquipped($itemToEquip->item, $service->inventory(), 'hammer'),
                'setEquipped'  => $hasSet,
                'setIndex'     => $setIndex,
            ];
        }

        Cache::put($character->user->id . '-compareItemDetails' . $itemToEquip->id, $viewData, now()->addMinutes(10));
    }

    public function buildShopData(Character $character, Item $item, string $type = null) {
        if ($type === 'bow') {
            $type = 'weapon';
        }

        if ($type === 'spell-damage' || $type === 'spell-healing') {
            $type = 'spell';
        }

        $service = $this->characterInventoryService->setCharacter($character)
            ->setPositions($this->validEquipPositionsValue->getPositions($item))
            ->setInventory($type);

        $setEquipped = $character->inventorySets()->where('is_equipped', true)->first();

        $hasSet   = !is_null($setEquipped);
        $setIndex = !is_null($setEquipped) ? $character->inventorySets->search(function($set) {return $set->is_equipped; }) + 1 : 0;

        return [
            'details'      => $this->equipItemService->getItemStats($item, $service->inventory(), $character),
            'itemToEquip'  => $item,
            'type'         => $service->getType($item, $type),
            'slotId'       => $item->id,
            'slotPosition' => null,
            'characterId'  => $character->id,
            'bowEquipped'  => $this->equipItemService->isTwoHandedItemEquipped($item, $service->inventory(), 'bow'),
            'hammerEquipped' => $this->equipItemService->isTwoHandedItemEquipped($item, $service->inventory(), 'hammer'),
            'setEquipped'  => $hasSet,
            'setIndex'     => $setIndex,
        ];
    }

    public function isItemUnique(InventorySlot $slot): bool {
        $item = $slot->item;

        if (!is_null($item->itemPrefix)) {
            return $item->itemPrefix->randomly_generated;
        }

        if (!is_null($item->itemSuffix)) {
            return $item->itemSuffix->randomly_generated;
        }

        return false;
    }

    public function characterHasUniqueEquipped(Character $character): bool {
        return $character->getInformation()->fetchInventory()->filter(function($slot) {
            $item = $slot->item;

            if (!is_null($item->itemPrefix)) {
                if ($item->itemPrefix->randomly_generated) {
                    return $slot;
                }
            }

            if (!is_null($item->itemSuffix)) {
                if ($item->itemSuffix->randomly_generated) {
                    return $slot;
                }
            }
        })->isNotEmpty();
    }
}
