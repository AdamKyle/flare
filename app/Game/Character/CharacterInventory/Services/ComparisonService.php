<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\SetSlot;
use App\Game\Core\Items\Transformers\Api\UsableItemTransformer;
use App\Game\Core\Items\Transformers\EquippableItemTransformer;
use App\Game\Core\Values\ValidEquipPositionsValue;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as FractalItem;

class ComparisonService
{
    /**
     * @param ValidEquipPositionsValue $validEquipPositionsValue
     * @param CharacterInventoryService $characterInventoryService
     * @param EquipItemService $equipItemService
     * @param Manager $manager
     * @param EquippableItemTransformer $equippableItemTransformer
     * @param UsableItemTransformer $usableItemTransformer
     */
    public function __construct(
        private readonly ValidEquipPositionsValue $validEquipPositionsValue,
        private readonly CharacterInventoryService $characterInventoryService,
        private readonly EquipItemService $equipItemService,
        private readonly Manager $manager,
        private readonly EquippableItemTransformer $equippableItemTransformer,
        private readonly UsableItemTransformer $usableItemTransformer,
    ) {}

    /**
     * Build comparison data for an owned inventory item.
     *
     * @param Character $character
     * @param InventorySlot $itemToEquip
     * @return array|null
     */
    public function buildComparisonData(Character $character, InventorySlot $itemToEquip): ?array
    {
        $service = $this->characterInventoryService->setCharacter($character)
            ->setInventorySlot($itemToEquip)
            ->setPositions($this->validEquipPositionsValue->getPositions($itemToEquip->item))
            ->setInventory();

        $normalizedType = $service->getType($itemToEquip->item);

        if (is_null($normalizedType)) {
            return null;
        }

        $inventory = $service->inventory();

        $viewData = [
            'details' => [],
            'itemToEquip' => $this->buildItemDetails($itemToEquip),
            'type' => $normalizedType,
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
            $hasSet = ! is_null($setEquipped);
            $setIndex = ! is_null($setEquipped) ? $character->inventorySets->search(function ($set) {
                return $set->is_equipped;
            }) + 1 : 0;

            $viewData = [
                'details' => $this->equipItemService->getItemStats($itemToEquip->item, $inventory, $character),
                'itemToEquip' => $this->buildItemDetails($itemToEquip),
                'type' => $normalizedType,
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

    /**
     * Build comparison data for an Alchemy Bag item.
     *
     * @param Character $character
     * @param AlchemyBagSlot $slot
     * @return array
     */
    public function buildAlchemyBagComparisonData(Character $character, AlchemyBagSlot $slot): array
    {
        $item = $this->manager->createData(new FractalItem($slot->item, $this->usableItemTransformer))->toArray()['data'];
        $item['slot_id'] = $slot->id;

        return [
            'details' => [],
            'itemToEquip' => $item,
            'type' => 'alchemy',
            'slotId' => $slot->id,
            'characterId' => $character->id,
            'bowEquipped' => false,
            'setEquipped' => false,
            'hammerEquipped' => false,
            'staveEquipped' => false,
            'setIndex' => 0,
        ];
    }

    /**
     * Build comparison data for an Inventory Set item.
     *
     * @param Character $character
     * @param SetSlot $slot
     * @return array
     */
    public function buildSetSlotComparisonData(Character $character, SetSlot $slot): array
    {
        $item = $this->manager->createData(new FractalItem($slot, $this->equippableItemTransformer))->toArray()['data'];
        $item['slot_id'] = $slot->id;

        return [
            'details' => [],
            'itemToEquip' => $item,
            'type' => $slot->item->type,
            'slotId' => $slot->id,
            'characterId' => $character->id,
            'bowEquipped' => false,
            'setEquipped' => false,
            'hammerEquipped' => false,
            'staveEquipped' => false,
            'setIndex' => 0,
        ];
    }

    /**
     * Build equipped-item comparison details and the catalog identity for a Shop item.
     *
     * @param Character $character
     * @param Item $item
     * @return array
     */
    public function buildShopData(Character $character, Item $item): array
    {
        $service = $this->characterInventoryService->setCharacter($character)
            ->setPositions($this->validEquipPositionsValue->getPositions($item))
            ->setInventory();

        return [
            'details' => $this->equipItemService->getItemStats($item, $service->inventory(), $character),
            'item_to_equip' => [
                'item_id' => $item->id,
                'name' => $item->affix_name,
                'type' => $item->type,
                'cost' => $item->cost,
                'slot_id' => null,
            ],
        ];
    }

    /**
     * Determine whether the character has an item type equipped.
     *
     * @param Character $character
     * @param string $type
     * @return bool
     */
    public function hasTypeEquipped(Character $character, string $type): bool
    {
        return $character->getInformation()->fetchInventory()->filter(function ($slot) use ($type) {
            return $slot->item->type === $type;
        })->isNotEmpty();
    }

    /**
     * Transform an inventory slot into equippable item details including its affix name.
     *
     * @param InventorySlot $slot
     * @return array
     */
    private function buildItemDetails(InventorySlot $slot): array
    {
        $data = $this->manager->createData(new FractalItem($slot, $this->equippableItemTransformer))->toArray()['data'];

        $data['affix_name'] = $slot->item->affix_name;

        return $data;
    }
}
