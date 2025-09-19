<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Flare\Items\Transformers\EquippableItemTransformer;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Transformers\ItemTransformer;
use App\Flare\Transformers\UsableItemTransformer;
use App\Game\Core\Values\ValidEquipPositionsValue;
use App\Game\Gems\Services\ItemAtonements;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as FractalItem;

class ComparisonService
{
    private ValidEquipPositionsValue $validEquipPositionsValue;
    private CharacterInventoryService $characterInventoryService;
    private EquipItemService $equipItemService;
    private ItemAtonements $itemAtonements;

    public function __construct(
        ValidEquipPositionsValue $validEquipPositionsValue,
        CharacterInventoryService $characterInventoryService,
        EquipItemService $equipItemService,
        ItemAtonements $itemAtonements,
    ) {
        $this->validEquipPositionsValue = $validEquipPositionsValue;
        $this->characterInventoryService = $characterInventoryService;
        $this->equipItemService = $equipItemService;
        $this->itemAtonements = $itemAtonements;
    }

    public function buildComparisonData(Character $character, InventorySlot $itemToEquip, string $type): array
    {
        $service = $this->characterInventoryService->setCharacter($character)
            ->setInventorySlot($itemToEquip)
            ->setPositions($this->validEquipPositionsValue->getPositions($itemToEquip->item))
            ->setInventory();

        $inventory = $service->inventory();

        $viewData = [
            'details' => [],
            'atonement' => $this->itemAtonements->getAtonements($itemToEquip->item, $inventory),
            'itemToEquip' => $itemToEquip->item->type === 'alchemy'
                ? $this->buildUsableItemDetails($itemToEquip)
                : $this->buildItemDetails($itemToEquip),
            'type' => $service->getType($itemToEquip->item),
            'slotId' => $itemToEquip->id,
            'characterId' => $character->id,
            'bowEquipped' => $this->hasTypeEquipped($character, 'bow'),
            'setEquipped' => false,
            'hammerEquipped' => $this->hasTypeEquipped($character, 'hammer'),
            'staveEquipped' => $this->hasTypeEquipped($character, 'stave'),
            'setIndex' => 0,
        ];

        if ($service->inventory()->isNotEmpty()) {
            $setEquipped = $character->inventorySets()->where('is_equipped', true)->first();
            $hasSet = !is_null($setEquipped);
            $setIndex = !is_null($setEquipped) ? $character->inventorySets->search(function ($set) {
                    return $set->is_equipped;
                }) + 1 : 0;

            $viewData = [
                'details' => $this->equipItemService->getItemStats($itemToEquip->item, $inventory, $character),
                'atonement' => $this->itemAtonements->getAtonements($itemToEquip->item, $inventory),
                'itemToEquip' => $this->buildItemDetails($itemToEquip),
                'type' => $service->getType($itemToEquip->item),
                'slotId' => $itemToEquip->id,
                'slotPosition' => $itemToEquip->position,
                'characterId' => $character->id,
                'bowEquipped' => $this->hasTypeEquipped($character, 'bow'),
                'hammerEquipped' => $this->hasTypeEquipped($character, 'hammer'),
                'staveEquipped' => $this->hasTypeEquipped($character, 'stave'),
                'setEquipped' => $hasSet,
                'setIndex' => $setIndex,
            ];
        }

        return $viewData;
    }

    public function buildShopData(Character $character, Item $item, ?string $type = null)
    {
        $service = $this->characterInventoryService->setCharacter($character)
            ->setPositions($this->validEquipPositionsValue->getPositions($item))
            ->setInventory();

        $setEquipped = $character->inventorySets()->where('is_equipped', true)->first();
        $hasSet = !is_null($setEquipped);
        $setIndex = !is_null($setEquipped) ? $character->inventorySets->search(function ($set) {
                return $set->is_equipped;
            }) + 1 : 0;

        $inventory = $service->inventory();

        return [
            'details' => $this->equipItemService->getItemStats($item, $inventory, $character),
        ];
    }

    public function hasTypeEquipped(Character $character, string $type): bool
    {
        return $character->getInformation()->fetchInventory()->filter(function ($slot) use ($type) {
            return $slot->item->type === $type;
        })->isNotEmpty();
    }

    protected function buildItemDetails(InventorySlot $slot): array
    {
        return $this->transformSlotWithEquippableTransformer($slot);
    }

    protected function buildUsableItemDetails(InventorySlot $slot): array
    {
        return $this->transformSlotWithEquippableTransformer($slot);
    }

    protected function itemDetails(Item $item): array
    {
        $resource = new FractalItem($item, new ItemTransformer());
        return (new Manager)->createData($resource)->toArray()['data'];
    }

    private function transformSlotWithEquippableTransformer(InventorySlot $slot): array
    {
        $resource = new FractalItem($slot, new EquippableItemTransformer());
        $data = (new Manager)->createData($resource)->toArray()['data'];

        if (!array_key_exists('affix_name', $data)) {
            $data['affix_name'] = $slot->item->affix_name;
        }

        return $data;
    }
}
