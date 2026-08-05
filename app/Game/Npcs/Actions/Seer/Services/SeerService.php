<?php

namespace App\Game\Npcs\Actions\Seer\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GemBagSlot;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Pagination\Pagination;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Items\Transformers\CraftingItemPreviewTransformer;
use App\Game\Core\Items\Values\ArmourType;
use App\Game\Core\Items\Values\ItemType;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Gems\Services\GemComparison;
use App\Game\Messages\Types\NpcMessageTypes;
use App\Game\Npcs\Actions\Seer\Transformers\SeerGemTransformer;
use App\Game\Npcs\Actions\Seer\Transformers\SeerInventoryItemTransformer;
use Facades\App\Game\Core\Handlers\DuplicateItemHandler;
use Facades\App\Game\Core\Handlers\HandleGoldBarsAsACurrency;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class SeerService
{
    use ResponseBuilder;

    const SOCKET_COST = 2000;

    const GEM_ATTACH_COST = 500;

    const REMOVE_GEM = 10;

    private GemComparison $gemComparison;

    public function __construct(
        GemComparison $gemComparison,
        private readonly RandomNumberGenerator $randomNumberGenerator,
        private readonly Pagination $pagination,
        private readonly SeerInventoryItemTransformer $seerInventoryItemTransformer,
        private readonly SeerGemTransformer $seerGemTransformer,
        private readonly CraftingItemPreviewTransformer $craftingItemPreviewTransformer,
    ) {
        $this->gemComparison = $gemComparison;
    }

    /**
     * Fetches a paginated, searchable list of items for the given purpose.
     *
     * "sockets" returns every socketable item. "attach" returns only items that already have sockets.
     */
    public function fetchPaginatedItems(Character $character, string $purpose, int $perPage, int $page, string $search = ''): array
    {
        $inventory = Inventory::where('character_id', $character->id)->first();

        $eligibleTypes = [
            ItemType::WEAPON->value,
            ItemType::STAVE->value,
            ItemType::BOW->value,
            ItemType::HAMMER->value,
            ArmourType::SHIELD->value,
            ArmourType::BODY->value,
            ArmourType::SLEEVES->value,
            ArmourType::HELMET->value,
            ArmourType::FEET->value,
            ArmourType::LEGGINGS->value,
            ArmourType::GLOVES->value,
        ];

        $query = InventorySlot::with(['item' => function ($itemQuery) {
            $itemQuery->with(['itemPrefix', 'itemSuffix', 'appliedHolyStacks', 'itemSkillProgressions'])
                ->withCount('sockets');
        }])
            ->where('inventory_id', $inventory->id)
            ->whereHas('item', function ($itemQuery) use ($eligibleTypes, $purpose) {
                $itemQuery->whereNotNull('socket_count')->whereIn('type', $eligibleTypes);

                if ($purpose === 'attach') {
                    $itemQuery->where('socket_count', '>', 0);
                }
            });

        if ($search !== '') {
            $query->whereHas('item', function ($itemQuery) use ($search) {
                $itemQuery->where('name', 'LIKE', '%'.$search.'%')
                    ->orWhereHas('itemPrefix', fn ($prefixQuery) => $prefixQuery->where('name', 'LIKE', '%'.$search.'%'))
                    ->orWhereHas('itemSuffix', fn ($suffixQuery) => $suffixQuery->where('name', 'LIKE', '%'.$search.'%'));
            });
        }

        $paginator = $query->orderBy('id')->paginate($perPage, ['*'], 'page', $page);

        return $this->pagination->transformLengthAwarePaginator($paginator, $this->seerInventoryItemTransformer);
    }

    /**
     * Fetches a paginated, searchable list of Gems available in the character's Gem Bag.
     */
    public function fetchPaginatedGems(Character $character, int $perPage, int $page, string $search = ''): array
    {
        if (is_null($character->gemBag)) {
            return $this->pagination->paginateCollectionResponse(collect(), $perPage, $page);
        }

        $query = GemBagSlot::with('gem')->where('gem_bag_id', $character->gemBag->id);

        if ($search !== '') {
            $query->whereHas('gem', function ($gemQuery) use ($search) {
                $gemQuery->where('name', 'LIKE', '%'.$search.'%');
            });
        }

        $paginator = $query->orderBy('id')->paginate($perPage, ['*'], 'page', $page);

        return $this->pagination->transformLengthAwarePaginator($paginator, $this->seerGemTransformer);
    }

    /**
     * Fetches a paginated, searchable list of items that currently have removable Gems attached.
     */
    public function fetchPaginatedItemsWithGems(Character $character, int $perPage, int $page, string $search = ''): array
    {
        $inventory = Inventory::where('character_id', $character->id)->first();

        $eligibleTypes = [
            ItemType::WEAPON->value,
            ItemType::STAVE->value,
            ItemType::BOW->value,
            ItemType::HAMMER->value,
            ArmourType::SHIELD->value,
            ArmourType::BODY->value,
            ArmourType::SLEEVES->value,
            ArmourType::HELMET->value,
            ArmourType::FEET->value,
            ArmourType::LEGGINGS->value,
            ArmourType::GLOVES->value,
        ];

        $query = InventorySlot::with(['item' => function ($itemQuery) {
            $itemQuery->with(['itemPrefix', 'itemSuffix', 'appliedHolyStacks', 'itemSkillProgressions'])
                ->withCount('sockets');
        }])
            ->where('inventory_id', $inventory->id)
            ->whereHas('item', function ($itemQuery) use ($eligibleTypes) {
                $itemQuery->whereNotNull('socket_count')
                    ->whereIn('type', $eligibleTypes)
                    ->whereHas('sockets');
            });

        if ($search !== '') {
            $query->whereHas('item', function ($itemQuery) use ($search) {
                $itemQuery->where('name', 'LIKE', '%'.$search.'%')
                    ->orWhereHas('itemPrefix', fn ($prefixQuery) => $prefixQuery->where('name', 'LIKE', '%'.$search.'%'))
                    ->orWhereHas('itemSuffix', fn ($suffixQuery) => $suffixQuery->where('name', 'LIKE', '%'.$search.'%'));
            });
        }

        $paginator = $query->orderBy('id')->paginate($perPage, ['*'], 'page', $page);

        return $this->pagination->transformLengthAwarePaginator($paginator, $this->seerInventoryItemTransformer);
    }

    /**
     * Get the Seer Camp action costs in Gold Bars.
     */
    public function getCosts(): array
    {
        return [
            'socket' => self::SOCKET_COST,
            'attach' => self::GEM_ATTACH_COST,
            'replace' => self::REMOVE_GEM,
            'remove_one' => self::REMOVE_GEM,
        ];
    }

    /**
     * Get items that we can assign gems to.
     */
    public function getItems(Character $character, bool $isManagingGems = false): array
    {
        $slots = $character->inventory->slots->whereNotNull('item.socket_count')->whereIn('item.type', [
            ItemType::WEAPON->value,
            ItemType::STAVE->value,
            ItemType::BOW->value,
            ItemType::HAMMER->value,
            ArmourType::SHIELD->value,
            ArmourType::BODY->value,
            ArmourType::SLEEVES->value,
            ArmourType::HELMET->value,
            ArmourType::FEET->value,
            ArmourType::LEGGINGS->value,
            ArmourType::GLOVES->value,
        ]);

        if ($isManagingGems) {
            $slots = $slots->filter(fn ($slot) => $slot->item->socket_count > 0);
        }

        return $slots->map(fn ($slot) => $this->seerInventoryItemTransformer->transform($slot))->values()->toArray();
    }

    /**
     * Get gems to attach.
     */
    public function getGems(Character $character): array
    {
        return $character->gemBag->gemSlots->map(fn ($slot) => $this->seerGemTransformer->transform($slot))->values()->toArray();
    }

    /**
     * Create Sockets.
     */
    public function createSockets(Character $character, int $inventorySlotId): array
    {
        $slot = $character->inventory->slots->find($inventorySlotId);

        if (is_null($slot)) {
            return $this->errorResult('No item was found to apply sockets to.');
        }

        if ($slot->item->type === 'trinket' || $slot->item->type === 'artifact') {
            return $this->errorResult('Trinkets and Artifacts cannot have sockets on them.');
        }

        if (! HandleGoldBarsAsACurrency::hasTheGoldBars($character->kingdoms, self::SOCKET_COST)) {
            return $this->errorResult('You do not have the gold bars to do this.');
        }

        $oldSocketCount = $slot->item->socket_count;

        $this->assignSocketCount($slot);

        $slot = $slot->refresh();

        $newSocketCount = $slot->item->socket_count;

        $character = $character->refresh();

        HandleGoldBarsAsACurrency::subtractCostFromKingdoms($character->kingdoms, self::SOCKET_COST);

        $message = 'The Seer attaches the sockets to: '.$slot->item->affix_name.' with their dark magics';

        ServerMessageHandler::handleMessage($character->user, NpcMessageTypes::SEER_ACTIONS, $message, $slot->id);

        if ($oldSocketCount === $newSocketCount) {
            $message = 'Failed to attach new sockets. "Sorry child. I tried." He takes your money anyways ...';
        } else {
            $message = 'Attached sockets to item! (Old Socket Count: '.$oldSocketCount.', New Count: '.$newSocketCount.').';
        }

        return $this->successResult([
            'items' => $this->getItems($character),
            'gems' => $this->getGems($character),
            'costs' => $this->getCosts(),
            'message' => $message,
            'result_preview' => $this->craftingItemPreviewTransformer->transform($slot->item, $slot->id),
        ]);
    }

    /**
     * Fetch Gems With items For Removal.
     */
    public function fetchGemsWithItemsForRemoval(Character $character): array
    {
        $items = $this->getItems($character);
        $gems = [];

        foreach ($items as $item) {
            $socketWithItem = InventorySlot::where('inventory_id', $character->inventory->id)->where('id', $item['slot_id'])->first();

            $attachedGemCount = $socketWithItem->item->sockets->count();

            $gems[] = [
                'slot_id' => $item['slot_id'],
                'gems' => $socketWithItem->item->sockets->map(function ($socket) {
                    return [
                        'gem_name' => $socket->gem->name,
                        'gem_id' => $socket->gem_id,
                    ];
                }),
                'comparison' => $this->gemComparison->ifItemGemsAreRemoved($socketWithItem->item),
                'remove_one_cost' => self::REMOVE_GEM,
                'remove_all_cost' => self::REMOVE_GEM * $attachedGemCount,
            ];
        }

        return $this->successResult([
            'items' => $items,
            'gems' => $gems,
            'costs' => $this->getCosts(),
        ]);
    }

    /**
     * Remove Gems From Item.
     */
    public function removeGem(Character $character, int $inventorySlotId, int $gemId): array
    {
        $slot = $character->inventory->slots->find($inventorySlotId);

        $validationResult = $this->gemRemovalValidation($character, $slot);

        if ($validationResult['status'] !== 200) {
            return $validationResult;
        }

        if (! HandleGoldBarsAsACurrency::hasTheGoldBars($character->kingdoms, self::REMOVE_GEM)) {
            return $this->errorResult('You do not have the gold bars to do this.');
        }

        $slot = $this->removeGemFromItem($character, $slot, $gemId);

        if (is_null($slot)) {
            return $this->errorResult('Item does not have specified gem.');
        }

        $message = 'The Seer removes the gem from: '.$slot->item->affix_name.'. The air crackles with magic.';

        ServerMessageHandler::handleMessage($character->user, NpcMessageTypes::SEER_ACTIONS, $message, $slot->id);

        $character = $character->refresh();

        $result = $this->fetchGemsWithItemsForRemoval($character);

        return $this->successResult([
            'items' => $this->getItems($character, true),
            'gems' => $this->getGems($character),
            'removal_data' => [
                'items' => $result['items'],
                'gems' => $result['gems'],
            ],
            'costs' => $this->getCosts(),
            'message' => 'Gem has been removed from the socket!',
        ]);
    }

    /**
     * Remove all gems from the item.
     */
    public function removeAllGems(Character $character, int $slotId): array
    {
        $slot = $character->inventory->slots->find($slotId);

        $validationResult = $this->gemRemovalValidation($character, $slot);

        if ($validationResult['status'] !== 200) {
            return $validationResult;
        }

        $socketCount = $slot->item->sockets->count();

        if (! $character->canAddToGemBag($socketCount)) {
            return $this->errorResult('Not enough room in your Gem Bag to remove all the gems on this item.');
        }

        if (! HandleGoldBarsAsACurrency::hasTheGoldBars($character->kingdoms, self::REMOVE_GEM * $socketCount)) {
            return $this->errorResult('You do not have the gold bars to do this.');
        }

        foreach ($slot->item->sockets as $socket) {
            $slot = $this->removeGemFromItem($character, $slot, $socket->gem_id);
        }

        $character = $character->refresh();

        $message = 'The Seer removes all gems from: '.$slot->item->affix_name.'. The seer is exhausted!';

        ServerMessageHandler::handleMessage($character->user, NpcMessageTypes::SEER_ACTIONS, $message, $slot->id);

        $result = $this->fetchGemsWithItemsForRemoval($character);

        return $this->successResult([
            'items' => $this->getItems($character, true),
            'gems' => $this->getGems($character),
            'removal_data' => [
                'items' => $result['items'],
                'gems' => $result['gems'],
            ],
            'costs' => $this->getCosts(),
            'message' => 'All gems have been removed!',
        ]);
    }

    /**
     * Replace the gem at the gem slot specified.
     */
    public function replaceGem(Character $character, int $slotId, int $gemSlotId, int $gemIdToReplace): array
    {

        $slot = $character->inventory->slots->find($slotId);
        $gemSlot = $character->gemBag->gemSlots->find($gemSlotId);

        if (is_null($slot)) {
            return $this->errorResult('No item was found to replace gem on.');
        }

        if (is_null($gemSlot)) {
            return $this->errorResult('The gem you want to use to replace the requested gem with, does not exist.');
        }

        if ($slot->item->sockets->isEmpty()) {
            return $this->errorResult('The item does not have any sockets. What are you doing?');
        }

        if (! $character->canAddToGemBag(1)) {
            return $this->errorResult('Your Gem Bag is full. Could not replace the gem.');
        }

        if (! HandleGoldBarsAsACurrency::hasTheGoldBars($character->kingdoms, self::REMOVE_GEM)) {
            return $this->errorResult('You do not have the gold bars to do this.');
        }

        $newItem = DuplicateItemHandler::duplicateItem($slot->item);

        $socket = $newItem->sockets->where('gem_id', $gemIdToReplace)->first();

        if (is_null($socket)) {
            return $this->errorResult('No Gem found on the item for the gem you want to replace.');
        }

        $character->gemBag->gemSlots()->create([
            'gem_bag_id' => $character->gemBag->id,
            'gem_id' => $socket->gem_id,
            'amount' => 1,
        ]);

        $socket->update([
            'gem_id' => $gemSlot->gem_id,
        ]);

        HandleGoldBarsAsACurrency::subtractCostFromKingdoms($character->kingdoms, self::REMOVE_GEM);

        $slot->update(['item_id' => $newItem->id]);

        $gemSlot->delete();

        $message = 'The Seer replaces a gem for: '.$slot->item->affix_name.'. The seer sees all.';

        ServerMessageHandler::handleMessage($character->user, NpcMessageTypes::SEER_ACTIONS, $message, $slot->id);

        $character = $character->refresh();

        return $this->successResult([
            'items' => $this->getItems($character, true),
            'gems' => $this->getGems($character),
            'costs' => $this->getCosts(),
            'message' => 'Gem has been replaced!',
        ]);
    }

    /**
     * Assign the gem to a socket.
     *
     * @return array
     */
    public function assignGemToSocket(Character $character, int $inventorySlotId, int $gemSlotId)
    {
        $slot = $character->inventory->slots->find($inventorySlotId);
        $gemSlot = $character->gemBag->gemSlots->find($gemSlotId);

        if (is_null($slot)) {
            return $this->errorResult('No item was found to add a gem to.');
        }

        if (is_null($gemSlot)) {
            return $this->errorResult('No gem to attach to supplied item was found.');
        }

        if ($slot->item->socket_count < 1) {
            return $this->errorResult('No Sockets on the supplied item. You need to add sockets to the item first.');
        }

        if ($slot->item->sockets->isNotEmpty() && $slot->item->sockets->count() >= $slot->item->socket_count) {
            return $this->errorResult(('Not enough sockets for this gem.'));
        }

        if (! HandleGoldBarsAsACurrency::hasTheGoldBars($character->kingdoms, self::GEM_ATTACH_COST)) {
            return $this->errorResult('You do not have the gold bars to do this.');
        }

        $this->addGemToItem($slot, $gemSlot);

        HandleGoldBarsAsACurrency::subtractCostFromKingdoms($character->kingdoms, self::GEM_ATTACH_COST);

        $character = $character->refresh();

        $message = 'The Seer adds a gem to: '.$slot->item->affix_name.'. The seer smiles as he hands you the item.';

        ServerMessageHandler::handleMessage($character->user, NpcMessageTypes::SEER_ACTIONS, $message, $slot->id);

        return $this->successResult([
            'items' => $this->getItems($character, true),
            'gems' => $this->getGems($character),
            'costs' => $this->getCosts(),
            'message' => 'Attached gem to item!',
        ]);
    }

    /**
     * Gem removal validation.SOCKET_COST
     *
     * - Common validation for removing gems.
     */
    protected function gemRemovalValidation(Character $character, ?InventorySlot $slot = null): array
    {
        if (is_null($slot)) {
            return $this->errorResult('No item was found to removed gem from.');
        }

        if (is_null($slot->item->socket_count) || $slot->item->socket_count <= 0) {
            return $this->errorResult('No sockets to remove gem from.');
        }

        if ($slot->item->sockets->isEmpty()) {
            return $this->errorResult('Sockets on this item are already empty.');
        }

        if (! $character->canAddToGemBag(1)) {
            return $this->errorResult('Your Gem Bag is full.');
        }

        return $this->successResult();
    }

    protected function addGemToItem(InventorySlot $slot, GemBagSlot $gemSlot): Item
    {
        $newItem = DuplicateItemHandler::duplicateItem($slot->item);

        $newItem->sockets()->create([
            'item_id' => $slot->item_id,
            'gem_id' => $gemSlot->gem_id,
        ]);

        $slot->update([
            'item_id' => $newItem->id,
        ]);

        $gemSlot->delete();

        return $newItem->refresh();
    }

    /**
     * Remove the gem from the item.
     */
    protected function removeGemFromItem(Character $character, InventorySlot $slot, int $gemId): ?InventorySlot
    {
        $newItem = DuplicateItemHandler::duplicateItem($slot->item);

        $socket = $newItem->sockets->where('gem_id', $gemId)->first();

        if (is_null($socket)) {
            return null;
        }

        $character->gemBag->gemSlots()->create([
            'gem_bag_id' => $character->gemBag->id,
            'gem_id' => $socket->gem_id,
            'amount' => 1,
        ]);

        $socket->delete();

        HandleGoldBarsAsACurrency::subtractCostFromKingdoms($character->kingdoms, self::REMOVE_GEM);

        $slot->update(['item_id' => $newItem->id]);

        return $slot->refresh();
    }

    /**
     * Get random type.
     */
    protected function getRandomType(): int
    {
        return $this->randomNumberGenerator->numberBetween(1, 100);
    }

    /**
     * Assign a random socket count (1-6 sockets)
     */
    protected function assignSocketCount(InventorySlot $slot): void
    {

        $newItem = DuplicateItemHandler::duplicateItem($slot->item);

        $type = $this->getRandomType();

        $socketCount = $slot->item->socket_count;

        if ($type > 99) {
            $newItem->update(['socket_count' => $socketCount > 6 ? $socketCount : 6]);

            $slot->update([
                'item_id' => $newItem->refresh()->id,
            ]);

            return;
        }

        if ($type >= 95) {
            $newItem->update(['socket_count' => $socketCount > 5 ? $socketCount : 5]);

            $slot->update([
                'item_id' => $newItem->refresh()->id,
            ]);

            return;
        }

        if ($type >= 80) {
            $newItem->update(['socket_count' => $socketCount > 4 ? $socketCount : 4]);

            $slot->update([
                'item_id' => $newItem->refresh()->id,
            ]);

            return;
        }

        if ($type >= 60) {
            $newItem->update(['socket_count' => $socketCount > 3 ? $socketCount : 3]);

            $slot->update([
                'item_id' => $newItem->refresh()->id,
            ]);

            return;
        }

        if ($type >= 50) {
            $newItem->update(['socket_count' => $socketCount > 2 ? $socketCount : 2]);

            $slot->update([
                'item_id' => $newItem->refresh()->id,
            ]);

            return;
        }

        if ($type >= 1) {
            $newItem->update(['socket_count' => $socketCount > 1 ? $socketCount : 1]);
        }

        $slot->update([
            'item_id' => $newItem->refresh()->id,
        ]);
    }
}
