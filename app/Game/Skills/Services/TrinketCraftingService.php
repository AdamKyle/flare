<?php

namespace App\Game\Skills\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\Skill;
use App\Flare\Pagination\Pagination;
use App\Game\Character\CharacterInventory\Exceptions\BatchCraftingDestinationFullException;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Core\Items\Transformers\CraftingItemPreviewTransformer;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Types\CraftingMessageTypes;
use App\Game\Skills\Transformers\TrinketCraftingItemTransformer;
use Exception;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use Illuminate\Database\Eloquent\Builder;

class TrinketCraftingService
{
    private CraftingService $craftingService;

    private SkillCheckService $skillCheckService;

    private ItemListCostTransformerService $itemListCostTransformerService;

    private SkillService $skillService;

    private Pagination $pagination;

    public function __construct(
        CraftingService $craftingService,
        SkillCheckService $skillCheckService,
        ItemListCostTransformerService $itemListCostTransformerService,
        SkillService $skillService,
        Pagination $pagination,
        private readonly CraftingItemPreviewTransformer $craftingItemPreviewTransformer,
        private readonly TrinketCraftingItemTransformer $trinketCraftingItemTransformer,
    ) {
        $this->craftingService = $craftingService;
        $this->skillCheckService = $skillCheckService;
        $this->itemListCostTransformerService = $itemListCostTransformerService;
        $this->skillService = $skillService;
        $this->pagination = $pagination;
    }

    /**
     * Fetches a paginated, searchable list of Trinkets eligible for crafting.
     *
     * @throws Exception
     */
    public function fetchPaginatedItemsToCraft(Character $character, int $perPage, int $page, string $search = ''): array
    {
        $trinketrySkill = $this->fetchCharacterSkill($character);

        $query = $this->buildTrinketItemsQuery($trinketrySkill);

        if ($search !== '') {
            $query->where('name', 'LIKE', '%'.$search.'%');
        }

        $paginator = $query->orderBy('skill_level_required', 'asc')
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'page', $page);

        $paginator->setCollection(
            $this->itemListCostTransformerService->reduceCostForTrinketryItems($character, $paginator->getCollection(), false)
        );

        return $this->pagination->transformLengthAwarePaginator($paginator, $this->trinketCraftingItemTransformer);
    }

    /**
     * Fetch trinkets the player can craft.
     *
     * @throws Exception
     */
    public function fetchItemsToCraft(Character $character, bool $showMerchantMessage = true): array
    {
        $trinkentrySkill = $this->fetchCharacterSkill($character);

        $items = $this->buildTrinketItemsQuery($trinkentrySkill)
            ->orderBy('skill_level_required', 'asc')
            ->get();

        $reducedItems = $this->itemListCostTransformerService->reduceCostForTrinketryItems($character, $items, $showMerchantMessage);

        return $reducedItems->map(fn (Item $item) => $this->trinketCraftingItemTransformer->transform($item))->values()->toArray();
    }

    private function buildTrinketItemsQuery(Skill $trinketrySkill): Builder
    {
        return Item::with(['itemPrefix', 'itemSuffix', 'appliedHolyStacks', 'itemSkillProgressions'])
            ->where('type', 'trinket')
            ->where('skill_level_required', '<=', $trinketrySkill->level);
    }

    public function fetchSkillXP(Character $character): array
    {
        $skill = $this->fetchCharacterSkill($character);

        return [
            'current_xp' => $skill->xp,
            'next_level_xp' => $skill->xp_max,
            'skill_name' => $skill->name,
            'level' => $skill->level,
        ];
    }

    /**
     * Attempt to craft the item.
     *
     * - Removes currency
     * - Crafts, attempts to, item
     * - Attempts to give item to player
     *
     * @throws Exception
     */
    public function craft(Character $character, Item $item): array
    {
        $trinkentrySkill = $this->fetchCharacterSkill($character);

        if (! $this->canAfford($character, $item)) {
            event(new ServerMessageEvent($character->user, 'You do not have enough of the required currencies to craft this.'));

            return ['items' => $this->fetchItemsToCraft($character), 'result_preview' => null];
        }

        if ($trinkentrySkill->level < $item->skill_level_required) {
            ServerMessageHandler::handlemessage($character->user, CraftingMessageTypes::TO_HARD_TO_CRAFT);

            return ['items' => $this->fetchItemsToCraft($character), 'result_preview' => null];
        }

        if ($trinkentrySkill->level > $item->skill_level_trivial) {
            ServerMessageHandler::handlemessage($character->user, CraftingMessageTypes::TO_EASY_TO_CRAFT);

            $this->deductCraftingCost($character, $item);

            $this->craftingService->pickUpItem($character, $item, $trinkentrySkill, true);

            return [
                'items' => $this->fetchItemsToCraft($character),
                'result_preview' => $this->buildResultPreview(),
            ];
        }

        $this->deductCraftingCost($character, $item);

        if (! $this->canCraft($trinkentrySkill)) {
            event(new ServerMessageEvent($character->user, 'You failed to craft the trinket. All your efforts fall apart before your eyes!'));

            return ['items' => $this->fetchItemsToCraft($character), 'result_preview' => null];
        }

        $this->craftingService->pickUpItem($character, $item, $trinkentrySkill);

        return [
            'items' => $this->fetchItemsToCraft($character->refresh(), false),
            'result_preview' => $this->buildResultPreview(),
        ];
    }

    /**
     * Build the preview for the trinket inventory slot created by the most recent pickUpItem() call.
     */
    private function buildResultPreview(): ?array
    {
        $inventorySlotId = $this->craftingService->getLastCraftedInventorySlotId();

        if (is_null($inventorySlotId)) {
            return null;
        }

        $slot = InventorySlot::with(['item.itemPrefix', 'item.itemSuffix', 'item.appliedHolyStacks'])->find($inventorySlotId);

        if (is_null($slot) || is_null($slot->item)) {
            return null;
        }

        return $this->craftingItemPreviewTransformer->transform($slot->item, $slot->id);
    }

    /**
     * Craft a trinket directly for Batch Crafting, with no InventorySlot involved.
     *
     * Preserves the same affordability, skill requirement, success/failure roll, and
     * currency spending rules as craft(), but never picks the item up into inventory.
     *
     * @throws Exception
     */
    public function craftForBatch(
        Character $character,
        Item $item,
        bool $suppressSuccessServerMessage = false,
        ?callable $destinationCreator = null,
    ): array {
        $trinkentrySkill = $this->fetchCharacterSkill($character);

        if (! $this->canAfford($character, $item)) {
            return [
                'success' => false,
                'item' => null,
                'reason' => 'not_enough_currency',
                'destination' => null,
                'cost' => $this->craftingCost($character->refresh(), $item),
            ];
        }

        if ($trinkentrySkill->level < $item->skill_level_required) {
            ServerMessageHandler::handlemessage($character->user, CraftingMessageTypes::TO_HARD_TO_CRAFT);

            return ['success' => false, 'item' => null, 'reason' => 'skill_too_low', 'destination' => null];
        }

        if ($trinkentrySkill->level > $item->skill_level_trivial) {
            $destination = is_null($destinationCreator) ? null : $destinationCreator($item);

            if (! is_null($destinationCreator) && ! is_array($destination)) {
                throw new BatchCraftingDestinationFullException('The retained Batch Crafting destination could not accept the crafted trinket.');
            }

            ServerMessageHandler::handlemessage($character->user, CraftingMessageTypes::TO_EASY_TO_CRAFT);

            $this->deductCraftingCost($character, $item);

            if (! $suppressSuccessServerMessage) {
                ServerMessageHandler::handleMessage($character->user, CraftingMessageTypes::CRAFTED, $item->name);
            }

            return ['success' => true, 'item' => $item, 'reason' => null, 'destination' => $destination];
        }

        if (! $this->canCraft($trinkentrySkill)) {
            $this->deductCraftingCost($character, $item);

            event(new ServerMessageEvent($character->user, 'You failed to craft the trinket. All your efforts fall apart before your eyes!'));

            return ['success' => false, 'item' => null, 'reason' => 'failed_roll', 'destination' => null];
        }

        $destination = is_null($destinationCreator) ? null : $destinationCreator($item);

        if (! is_null($destinationCreator) && ! is_array($destination)) {
            throw new BatchCraftingDestinationFullException('The retained Batch Crafting destination could not accept the crafted trinket.');
        }

        $this->deductCraftingCost($character, $item);

        if (! $suppressSuccessServerMessage) {
            ServerMessageHandler::handleMessage($character->user, CraftingMessageTypes::CRAFTED, $item->name);
        }

        $this->skillService->assignXpToCraftingSkill($character->map->gameMap, $trinkentrySkill);

        return ['success' => true, 'item' => $item, 'reason' => null, 'destination' => $destination];
    }

    /**
     * Fetch the crafting skill for the player.
     */
    protected function fetchCharacterSkill(Character $character): Skill
    {
        $gameSkill = GameSkill::where('name', 'Trinketry')->first();

        return $character->skills()->where('game_skill_id', $gameSkill->id)->first();
    }

    /**
     * Can the player afford to make this item?
     *
     * @throws Exception
     */
    public function craftingCost(Character $character, Item $item): array
    {
        $copperCoinCost = (int) $item->copper_coin_cost;
        $goldDustCost = (int) $item->gold_dust_cost;

        if ($character->classType()->isMerchant()) {
            $copperCoinCost = floor($copperCoinCost - $copperCoinCost * 0.10);
            $goldDustCost = floor($goldDustCost - $goldDustCost * 0.10);
        }

        return [
            'item_id' => $item->id,
            'item_name' => $item->name,
            'gold_dust' => [
                'required' => (int) $goldDustCost,
                'available' => (int) $character->gold_dust,
                'missing' => max(0, (int) $goldDustCost - (int) $character->gold_dust),
            ],
            'copper_coins' => [
                'required' => (int) $copperCoinCost,
                'available' => (int) $character->copper_coins,
                'missing' => max(0, (int) $copperCoinCost - (int) $character->copper_coins),
            ],
        ];
    }

    protected function canAfford(Character $character, Item $item): bool
    {
        $cost = $this->craftingCost($character, $item);

        return $cost['gold_dust']['missing'] === 0
            && $cost['copper_coins']['missing'] === 0;
    }

    private function deductCraftingCost(Character $character, Item $item): void
    {
        $cost = $this->craftingCost($character, $item);

        $character->update([
            'gold_dust' => $character->gold_dust - $cost['gold_dust']['required'],
            'copper_coins' => $character->copper_coins - $cost['copper_coins']['required'],
        ]);

        event(new UpdateCharacterCurrenciesEvent($character->refresh()));
    }

    /**
     * Can the character craft this item?
     */
    protected function canCraft(Skill $trinketSkill): bool
    {
        return $this->skillCheckService->characterRoll($trinketSkill) > $this->skillCheckService->getDCCheck($trinketSkill);
    }
}
