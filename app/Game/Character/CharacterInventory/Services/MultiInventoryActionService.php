<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySet;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\MarketBoard;
use App\Flare\Models\SetSlot;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\Character\CharacterInventory\Builders\EquipManyBuilder;
use App\Game\Character\CharacterInventory\Exceptions\EquipItemException;
use App\Game\Character\CharacterInventory\Jobs\DisenchantMany;
use App\Game\Character\CharacterInventory\Transformers\CharacterInventoryCountTransformer;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Shop\Services\ShopService;
use App\Game\Skills\Services\DisenchantManyService;
use Exception;
use Facades\App\Game\Core\Items\Pricing\SellItemCalculator;
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

        $character = $character->refresh();

        return $this->successResult([
            'message' => 'Moved all selected items to: '.$result['moved_to_set_name'].'.',
            'inventory' => $this->characterInventoryService->setCharacter($character)->getInventoryForApi(),
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
            ->whereHas('item', function ($query) {
                return $query->whereNotIn('type', ['alchemy', 'gem', 'quest', 'artifact', 'trinket']);
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

        $totalSoldFor = $slots->sum(fn (InventorySlot $slot) => SellItemCalculator::fetchSalePriceWithAffixes($slot->item));
        $slotCount = $slots->count();
        $character->increment('gold', $totalSoldFor);
        $character->inventory->slots()->whereIn('id', $slots->pluck('id')->all())->delete();

        $character = $character->refresh();

        $data = new Item($character, $this->characterInventoryCountTransformer);
        $data = $this->manager->createData($data)->toArray();

        return $this->successResult([
            'message' => 'Sold all items for: '.number_format($totalSoldFor).' Gold (Minus 5% on each sale)',
            'inventory_count' => $data,
        ]);
    }

    public function sellManySetSlots(Character $character, InventorySet $set, array $setSlotIds): array
    {
        if ($set->character_id !== $character->id) {
            return $this->errorResult('Cannot do that.');
        }

        $slots = $set->slots()
            ->whereIn('id', $setSlotIds)
            ->whereHas('item', function ($query) {
                return $query->whereNotIn('type', ['alchemy', 'gem', 'quest', 'artifact', 'trinket']);
            })
            ->with('item')
            ->get();

        $totalSoldFor = $slots->sum(fn (SetSlot $slot) => SellItemCalculator::fetchSalePriceWithAffixes($slot->item));
        $slotCount = $slots->count();
        $character->increment('gold', $totalSoldFor);
        $set->slots()->whereIn('id', $slots->pluck('id')->all())->delete();

        $character = $character->refresh();

        event(new UpdateCharacterInventoryCountEvent($character));
        ServerMessageHandler::sendBasicMessage($character->user, 'Sold '.$slotCount.' set items for: '.number_format($totalSoldFor).' Gold (Minus 5% tax).');

        return $this->successResult([
            'message' => 'Sold selected set items for: '.number_format($totalSoldFor).' Gold (Minus 5% tax).',
            'inventory' => $this->characterInventoryService->setCharacter($character)->getInventoryForApi(),
        ]);
    }

    public function sellAllCraftedItemsSetSlots(Character $character, InventorySet $set): array
    {
        if ($set->character_id !== $character->id) {
            return $this->errorResult('Cannot do that.');
        }

        if (! $set->isBatchCraftingSet()) {
            return $this->errorResult('Cannot do that.');
        }

        $setSlotIds = $set->slots()->pluck('id')->all();

        $result = $this->sellManySetSlots($character, $set, $setSlotIds);

        $result['message'] = str_replace('Sold selected set items', 'Sold all set items', $result['message']);

        return $result;
    }

    public function disenchantManySetSlots(Character $character, InventorySet $set, array $setSlotIds): array
    {
        if ($set->character_id !== $character->id) {
            return $this->errorResult('Cannot do that.');
        }

        $filteredSlots = $set->slots()
            ->whereIn('id', $setSlotIds)
            ->whereHas('item', function ($query) {
                return $query->whereNotIn('type', ['alchemy', 'gem', 'quest', 'trinket', 'artifact']);
            })
            ->with('item')
            ->get()
            ->filter(function (SetSlot $slot) {
                return ! is_null($slot->item->item_prefix_id) || ! is_null($slot->item->item_suffix_id);
            });

        $itemIdsToDisenchant = $filteredSlots->pluck('item_id')->toArray();
        $filteredSlotIds = $filteredSlots->pluck('id')->toArray();

        $set->slots()->whereIn('id', $filteredSlotIds)->delete();

        $character = $character->refresh();

        DisenchantMany::dispatch($character, $itemIdsToDisenchant);

        return $this->successResult([
            'message' => 'Set items are queued for disenchanting. Check Server Messages
            (Scroll down for desktop, click Serve Messages tab). If on mobile scroll down,
            selected Server Messages from the Orange Chat Dropdown.',
            'inventory' => $this->characterInventoryService->setCharacter($character)->getInventoryForApi(),
        ]);
    }

    public function disenchantAllCraftedItemsSetSlots(Character $character, InventorySet $set): array
    {
        if ($set->character_id !== $character->id) {
            return $this->errorResult('Cannot do that.');
        }

        if (! $set->isBatchCraftingSet()) {
            return $this->errorResult('Cannot do that.');
        }

        $setSlotIds = $set->slots()->pluck('id')->all();

        $result = $this->disenchantManySetSlots($character, $set, $setSlotIds);

        $result['message'] = str_replace('Set items are queued', 'All eligible set items are queued', $result['message']);

        return $result;
    }

    /**
     * Disenchant many items via the DisenchantManyService.
     *
     * @param  array{ids?:array<int|string>,exclude?:array<int|string>}  $params
     * @return array{status:int,message:string,disenchanted_item:array<int,array{name:string,status:string,gold_dust:int}>}
     */
    public function disenchantManyItems(Character $character, array $params): array
    {
        return $this->disenchantManyService->disenchantMany(
            $this->manager,
            $this->characterInventoryCountTransformer,
            $character,
            $params,
        );
    }

    /**
     * Destroy items by include/exclude rules (artifacts excluded).
     *
     * @param  array{ids?:array<int|string>,exclude?:array<int|string>}  $params
     * @return array{status:int,message:string}
     */
    public function destroyManySetSlots(Character $character, InventorySet $set, array $setSlotIds): array
    {
        if ($set->character_id !== $character->id) {
            return $this->errorResult('Cannot do that.');
        }

        $set->slots()
            ->whereIn('id', $setSlotIds)
            ->whereHas('item', function ($query) {
                return $query->whereNotIn('type', ['alchemy', 'gem', 'quest', 'artifact']);
            })
            ->delete();

        $character = $character->refresh();

        event(new UpdateCharacterInventoryCountEvent($character));

        return $this->successResult([
            'message' => 'Destroyed all selected set items (with exception of artifacts. You must manually delete these powerful items).',
            'inventory' => $this->characterInventoryService->setCharacter($character)->getInventoryForApi(),
        ]);
    }

    public function destroyAllCraftedItemsSetSlots(Character $character, InventorySet $set): array
    {
        if ($set->character_id !== $character->id) {
            return $this->errorResult('Cannot do that.');
        }

        if (! $set->isBatchCraftingSet()) {
            return $this->errorResult('Cannot do that.');
        }

        $setSlotIds = $set->slots()->pluck('id')->all();

        return $this->destroyManySetSlots($character, $set, $setSlotIds);
    }

    public function listManySetSlots(Character $character, InventorySet $set, array $setSlotIds, int $listPrice): array
    {
        if ($set->character_id !== $character->id) {
            return $this->errorResult('Cannot do that.');
        }

        $slots = $set->slots()
            ->whereIn('id', $setSlotIds)
            ->whereHas('item', function ($query) {
                return $query->whereNotIn('type', ['alchemy', 'gem', 'quest', 'artifact', 'trinket']);
            })
            ->with('item')
            ->get();

        foreach ($slots as $slot) {
            MarketBoard::create([
                'character_id' => $character->id,
                'item_id' => $slot->item_id,
                'listed_price' => $listPrice,
            ]);
        }

        $slotCount = $slots->count();
        $set->slots()->whereIn('id', $slots->pluck('id')->all())->delete();

        $character = $character->refresh();

        event(new UpdateCharacterInventoryCountEvent($character));

        return $this->successResult([
            'message' => 'Listed '.$slotCount.' set items for: '.number_format($listPrice).' Gold each.',
            'inventory' => $this->characterInventoryService->setCharacter($character)->getInventoryForApi(),
        ]);
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
            ->whereHas('item', function ($query) {
                return $query->whereNotIn('type', ['alchemy', 'gem', 'quest', 'artifact']);
            })
            ->where('equipped', false);

        if (isset($params['exclude'])) {
            $excludeIds = array_map(static fn ($id): int => (int) $id, (array) $params['exclude']);
            $slotsQuery->whereNotIn('id', $excludeIds);
        } elseif (isset($params['ids'])) {
            $includeIds = array_map(static fn ($id): int => (int) $id, (array) $params['ids']);
            $slotsQuery->whereIn('id', $includeIds);
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
}
