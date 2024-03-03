<?php

namespace App\Game\CharacterInventory\Services;

use League\Fractal\Manager;
use League\Fractal\Resource\Item as FractalItem;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Transformers\ItemComparisonTransfromer;
use App\Flare\Transformers\ItemTransformer;
use App\Flare\Transformers\UsableItemTransformer;
use App\Game\Core\Values\ValidEquipPositionsValue;
use App\Game\Gems\Services\ItemAtonements;

class ComparisonService {

    /**
     * @var ValidEquipPositionsValue $validEquipPositionsValue
     */
    private ValidEquipPositionsValue $validEquipPositionsValue;

    /**
     * @var CharacterInventoryService $characterInventoryService
     */
    private CharacterInventoryService $characterInventoryService;

    /**
     * @var EquipItemService $equipItemService
     */
    private EquipItemService $equipItemService;

    /**
     * @var ItemAtonements $itemAtonements
     */
    private ItemAtonements $itemAtonements;

    /**
     * @param ValidEquipPositionsValue $validEquipPositionsValue
     * @param CharacterInventoryService $characterInventoryService
     * @param EquipItemService $equipItemService
     * @param ItemAtonements $itemAtonements
     */
    public function __construct(
        ValidEquipPositionsValue $validEquipPositionsValue,
        CharacterInventoryService $characterInventoryService,
        EquipItemService $equipItemService,
        ItemAtonements $itemAtonements,
    ) {
        $this->validEquipPositionsValue  = $validEquipPositionsValue;
        $this->characterInventoryService = $characterInventoryService;
        $this->equipItemService          = $equipItemService;
        $this->itemAtonements            = $itemAtonements;
    }

    /**
     * @param Character $character
     * @param InventorySlot $itemToEquip
     * @param string $type
     * @return array
     */
    public function buildComparisonData(Character $character, InventorySlot $itemToEquip, string $type): array {
        $service  = $this->characterInventoryService->setCharacter($character)
            ->setInventorySlot($itemToEquip)
            ->setPositions($this->validEquipPositionsValue->getPositions($itemToEquip->item))
            ->setInventory();

        $inventory = $service->inventory();

        $viewData = [
            'details'        => [],
            'atonement'      => $this->itemAtonements->getAtonements($itemToEquip->item, $inventory),
            'itemToEquip'    => $itemToEquip->item->type === 'alchemy' ? $this->buildUsableItemDetails($itemToEquip) : $this->buildItemDetails($itemToEquip),
            'type'           => $service->getType($itemToEquip->item),
            'slotId'         => $itemToEquip->id,
            'characterId'    => $character->id,
            'bowEquipped'    => $this->hasTypeEquipped($character, 'bow'),
            'setEquipped'    => false,
            'hammerEquipped' => $this->hasTypeEquipped($character, 'hammer'),
            'staveEquipped'  => $this->hasTypeEquipped($character, 'stave'),
            'setIndex'       => 0,
        ];

        if ($service->inventory()->isNotEmpty()) {
            $setEquipped = $character->inventorySets()->where('is_equipped', true)->first();


            $hasSet   = !is_null($setEquipped);
            $setIndex = !is_null($setEquipped) ? $character->inventorySets->search(function ($set) {
                // @codeCoverageIgnoreStart
                return $set->is_equipped;
                // @codeCoverageIgnoreEnd
            }) + 1 : 0;

            $viewData = [
                'details'        => $this->equipItemService->getItemStats($itemToEquip->item, $inventory, $character),
                'atonement'      => $this->itemAtonements->getAtonements($itemToEquip->item, $inventory),
                'itemToEquip'    => $this->buildItemDetails($itemToEquip),
                'type'           => $service->getType($itemToEquip->item),
                'slotId'         => $itemToEquip->id,
                'slotPosition'   => $itemToEquip->position,
                'characterId'    => $character->id,
                'bowEquipped'    => $this->hasTypeEquipped($character, 'bow'),
                'hammerEquipped' => $this->hasTypeEquipped($character, 'hammer'),
                'staveEquipped'  => $this->hasTypeEquipped($character, 'stave'),
                'setEquipped'    => $hasSet,
                'setIndex'       => $setIndex,
            ];
        }

        return $viewData;
    }

    public function buildShopData(Character $character, Item $item, string $type = null) {

        $service = $this->characterInventoryService->setCharacter($character)
            ->setPositions($this->validEquipPositionsValue->getPositions($item))
            ->setInventory();

        $setEquipped = $character->inventorySets()->where('is_equipped', true)->first();

        $hasSet   = !is_null($setEquipped);
        $setIndex = !is_null($setEquipped) ? $character->inventorySets->search(function ($set) {
            // @codeCoverageIgnoreStart
            return $set->is_equipped;
            // @codeCoverageIgnoreEnd
        }) + 1 : 0;

        $inventory = $service->inventory();

        return [
            'details'        => $this->equipItemService->getItemStats($item, $inventory, $character),
            'atonement'      => $this->itemAtonements->getAtonements($item, $inventory),
            'itemToEquip'    => $this->itemDetails($item),
            'type'           => $service->getType($item),
            'slotId'         => $item->id,
            'slotPosition'   => null,
            'characterId'    => $character->id,
            'bowEquipped'    => $this->hasTypeEquipped($character, 'bow'),
            'hammerEquipped' => $this->hasTypeEquipped($character, 'hammer'),
            'staveEquipped'  => $this->hasTypeEquipped($character, 'stave'),
            'setEquipped'    => $hasSet,
            'setIndex'       => $setIndex,
            'setName'        => !is_null($setEquipped) ? $setEquipped->name : null,
        ];
    }

    public function hasTypeEquipped(Character $character, string $type): bool {
        return $character->getInformation()->fetchInventory()->filter(function ($slot) use ($type) {
            return $slot->item->type === $type;
        })->isNotEmpty();
    }

    protected function buildItemDetails(InventorySlot $slot): array {
        if ($slot->item->type === 'quest') {
            $item = new FractalItem($slot->item, new ItemTransformer);
        } else {
            $item = new FractalItem($slot->item, new ItemComparisonTransfromer);
        }

        $item = (new Manager())->createData($item)->toArray()['data'];

        $item['slot_id'] = $slot->id;

        return $item;
    }

    protected function buildUsableItemDetails(InventorySlot $slot): array {

        $item = new FractalItem($slot->item, new UsableItemTransformer);

        $item = (new Manager())->createData($item)->toArray()['data'];

        $item['slot_id'] = $slot->id;

        return $item;
    }

    protected function itemDetails(Item $item): array {
        $item = new FractalItem($item, new ItemComparisonTransfromer);

        $item = (new Manager())->createData($item)->toArray()['data'];

        return $item;
    }
}
