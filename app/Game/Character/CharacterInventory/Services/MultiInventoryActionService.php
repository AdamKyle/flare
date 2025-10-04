<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Transformers\CharacterInventoryCountTransformer;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\Character\CharacterInventory\Builders\EquipManyBuilder;
use App\Game\Character\CharacterInventory\Exceptions\EquipItemException;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Shop\Events\SellItemEvent;
use App\Game\Shop\Services\ShopService;
use App\Game\Skills\Services\DisenchantManyService;
use Exception;
use Facades\App\Flare\Calculators\SellItemCalculator;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class MultiInventoryActionService
{
    use ResponseBuilder;

    public function __construct(
        private readonly InventorySetService $inventorySetService,
        private readonly EquipItemService $equipItemService,
        private readonly EquipManyBuilder $equipManyBuilder,
        private readonly ShopService $shopService,
        private readonly CharacterInventoryService $characterInventoryService,
        private readonly UpdateCharacterAttackTypesHandler $updateCharacterAttackTypesHandler,
        private readonly DisenchantManyService $disenchantManyService,
        private readonly Manager $manager,
        private readonly CharacterInventoryCountTransformer $characterInventoryCountTransformer,
    ) {}

    /**
     * Move multiple inventory slots to a specific set.
     *
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
            'message' => 'Moved all selected items to: '.$result['moved_to_set_name'].'.',
            'inventory' => $result['inventory'],
        ]);
    }

    /**
     * Equip multiple items.
     *
     * @return array{status:int,message:string,inventory:mixed}
     *
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
            'message' => 'Equipped valid items to your character.',
            'inventory' => $characterInventoryService->getInventoryForApi(),
        ]);
    }

    /**
     * Sell many items by include/exclude rules.
     *
     * @param  array{ids?:array<int|string>,exclude?:array<int|string>}  $params
     * @return array{status:int,message:string}
     *
     * @throws Exception
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
            $slotsQuery->whereNotIn('id', $excludeIds);
        } elseif (isset($params['ids'])) {
            $includeIds = array_map(static fn ($id): int => (int) $id, (array) $params['ids']);
            $slotsQuery->whereIn('id', $includeIds);
        }

        $slots = $slotsQuery->get();

        $totalSoldFor = 0;

        foreach ($slots as $slot) {
            $totalSoldFor += $this->sellItem($character, $slot);
        }

        $character = $character->refresh();

        $data = new Item($character, $this->characterInventoryCountTransformer);
        $data = $this->manager->createData($data)->toArray();

        return $this->successResult([
            'message' => 'Sold all items for: '.number_format($totalSoldFor).' Gold (Minus 5% on each sale)',
            'inventory_count' => $data,
        ]);
    }

    /**
     * Disenchant many items via the DisenchantManyService.
     *
     * @param  array{ids?:array<int|string>,exclude?:array<int|string>}  $params
     * @return array{status:int,message:string,disenchanted_item:array<int,array{name:string,status:string,gold_dust:int}>}
     */
    public function disenchantManyItems(Character $character, array $params): array
    {
        return $this->disenchantManyService->disenchantMany($this->manager, $this->characterInventoryCountTransformer, $character, $params);
    }

    /**
     * Destroy items by include/exclude rules (artifacts excluded).
     *
     * @param  array{ids?:array<int|string>,exclude?:array<int|string>}  $params
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

        $character = $character->refresh();

        $data = new Item($character, $this->characterInventoryCountTransformer);
        $data = $this->manager->createData($data)->toArray();

        return $this->successResult([
            'message' => 'Destroyed all selected selected items (with exception of artifacts. You must manually delete these powerful items. Click the item, click delete and confirm you want to do this, if you have the item.)',
            'inventory_count' => $data,
        ]);
    }

    /**
     * Equip a single item with prepared parameters.
     *
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
     * @throws Exception
     */
    private function sellItem(Character $character, InventorySlot $slot): int
    {
        $item = $slot->item;

        $totalSoldFor = SellItemCalculator::fetchSalePriceWithAffixes($item);

        event(new SellItemEvent($slot, $character));

        ServerMessageHandler::sendBasicMessage(
            $character->user,
            'Sold item: '.$item->affix_name.' for: '.number_format($totalSoldFor).' (Minus 5% tax) Gold! (Selling to a shop can never go above 2 billion gold for an individual item)'
        );

        return $totalSoldFor;
    }
}
