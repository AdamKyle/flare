<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Game\Character\CharacterInventory\Exceptions\EquipItemException;
use Exception;
use Facades\App\Flare\Calculators\SellItemCalculator;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\Character\CharacterInventory\Builders\EquipManyBuilder;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Shop\Events\SellItemEvent;
use App\Game\Shop\Services\ShopService;
use App\Game\Skills\Services\DisenchantManyService;

class MultiInventoryActionService
{
    use ResponseBuilder;

    /**
     * @param InventorySetService $inventorySetService
     * @param EquipItemService $equipItemService
     * @param EquipManyBuilder $equipManyBuilder
     * @param ShopService $shopService
     * @param CharacterInventoryService $characterInventoryService
     * @param UpdateCharacterAttackTypesHandler $updateCharacterAttackTypesHandler
     * @param DisenchantManyService $disenchantManyService
     */
    public function __construct(
        private readonly InventorySetService $inventorySetService,
        private readonly EquipItemService $equipItemService,
        private readonly EquipManyBuilder $equipManyBuilder,
        private readonly ShopService $shopService,
        private readonly CharacterInventoryService $characterInventoryService,
        private readonly UpdateCharacterAttackTypesHandler $updateCharacterAttackTypesHandler,
        private readonly DisenchantManyService $disenchantManyService,
    ) {}

    /**
     * Move multiple inventory slots to a specific set.
     *
     * @param Character $character
     * @param int $setId
     * @param array $slotIds
     * @return array{status:int,message:string,inventory?:mixed,moved_to_set_name?:string}
     */
    public function moveManyItemsToSelectedSet(Character $character, int $setId, array $slotIds): array
    {
        $result = [];

        $lastIndex = count($slotIds) - 1;

        foreach ($slotIds as $index => $slotId) {
            $isLast = $index === $lastIndex;

            $result = $this->inventorySetService->moveItemToSet($character, $slotId, $setId, false, $isLast);

            if (is_null($result)) {
                continue;
            }

            if ($result['status'] === 422) {
                return $result;
            }
        }

        return $this->successResult([
            'message'   => 'Moved all selected items to: ' . $result['moved_to_set_name'] . '.',
            'inventory' => $result['inventory'],
        ]);
    }

    /**
     * Equip multiple items.
     *
     * @param Character $character
     * @param array $slotIds
     * @return array{status:int,message:string,inventory:mixed}
     * @throws EquipItemException
     */
    public function equipManyItems(Character $character, array $slotIds): array
    {
        try {
            $itemsToEquip = $this->equipManyBuilder->buildEquipmentArray($character, $slotIds);
        } catch (Exception $e) {
            return $this->errorResult($e->getMessage());
        }

        foreach ($itemsToEquip as $toEquipItem) {
            $this->equipItem($character, $toEquipItem);
        }

        $character = $character->refresh();

        $this->updateCharacterAttackTypesHandler->updateCache($character);

        event(new UpdateCharacterInventoryCountEvent($character));

        $characterInventoryService = $this->characterInventoryService->setCharacter($character);

        return $this->successResult([
            'message'   => 'Equipped valid items to your character.',
            'inventory' => $characterInventoryService->getInventoryForApi(),
        ]);
    }

    /**
     * Sell many items by include/exclude rules.
     *
     * @param Character $character
     * @param array{ids?:array<int|string>,exclude?:array<int|string>} $params
     * @return array{status:int,message:string}
     */
    public function sellManyItems(Character $character, array $params): array
    {
        $slotsQuery = $character->inventory->slots()
            ->whereHas('item', static function ($query) {
                $query->whereNotIn('type', ['alchemy', 'quest', 'artifact', 'trinket']);
            })
            ->where('equipped', false);

        if (isset($params['exclude'])) {
            $excludeIds = array_map(static fn ($id): int => (int) $id, (array) $params['exclude']);
            $slotsQuery->whereNotIn('item_id', $excludeIds);
        } elseif (isset($params['ids'])) {
            $includeIds = array_map(static fn ($id): int => (int) $id, (array) $params['ids']);
            $slotsQuery->whereIn('item_id', $includeIds);
        }

        $slots = $slotsQuery->get();

        $totalSoldFor = 0;

        foreach ($slots as $slot) {
            $totalSoldFor += $this->sellItem($character, $slot);
        }

        return $this->successResult([
            'message' => 'Sold all items for: ' . number_format($totalSoldFor) . ' Gold (Minus 5% on each sale)',
        ]);
    }

    /**
     * Disenchant many items via the DisenchantManyService.
     *
     * @param Character $character
     * @param array{ids?:array<int|string>,exclude?:array<int|string>} $params
     * @return array{status:int,message:string,disenchanted_item:array<int,array{name:string,status:string,gold_dust:int}>}
     */
    public function disenchantManyItems(Character $character, array $params): array
    {
        return $this->disenchantManyService->disenchantMany($character, $params);
    }

    /**
     * Destroy items by include/exclude rules (artifacts excluded).
     *
     * @param Character $character
     * @param array{ids?:array<int|string>,exclude?:array<int|string>} $params
     * @return array{status:int,message:string}
     */
    public function destroyManyItems(Character $character, array $params): array
    {
        $slotsQuery = $character->inventory->slots()
            ->whereHas('item', static function ($query) {
                $query->whereNotIn('type', ['alchemy', 'quest', 'artifact', 'trinket']);
            })
            ->where('equipped', false);

        if (isset($params['exclude'])) {
            $excludeIds = $params['exclude'];
            $slotsQuery->whereNotIn('item_id', $excludeIds);
        } elseif (isset($params['ids'])) {
            $includeIds = $params['ids'];
            $slotsQuery->whereIn('item_id', $includeIds);
        }

        $slotsQuery->delete();

        return $this->successResult([
            'message' => 'Destroyed all selected selected items (with exception of artifacts. You must manually delete these powerful items. Click the item, click delete and confirm you want to do this, if you have the item.)',
        ]);
    }

    /**
     * Equip a single item with prepared parameters.
     *
     * @param Character $character
     * @param array $equipParams
     * @return void
     * @throws EquipItemException
     */
    private function equipItem(Character $character, array $equipParams): void
    {
        $this->equipItemService->setRequest($equipParams)
            ->setCharacter($character)
            ->replaceItem();
    }

    /**
     * Sell a single inventory slot and emit messages/events.
     *
     * @param Character $character
     * @param InventorySlot $slot
     * @return int
     */
    private function sellItem(Character $character, InventorySlot $slot): int
    {
        $item = $slot->item;

        $totalSoldFor = SellItemCalculator::fetchSalePriceWithAffixes($item);

        event(new SellItemEvent($slot, $character));

        ServerMessageHandler::sendBasicMessage(
            $character->user,
            'Sold item: ' . $item->affix_name . ' for: ' . number_format($totalSoldFor) . ' (Minus 5% tax) Gold! (Selling to a shop can never go above 2 billion gold for an individual item)'
        );

        return $totalSoldFor;
    }
}
