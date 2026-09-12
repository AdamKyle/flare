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
     * @param Character $character The character requesting the list.
     * @param int $perPage The number of items per page.
     * @param int $page The requested page number.
     * @param string $search The optional item name search text.
     * @return array The paginated craftable Trinket payload.
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
     * @param Character $character The character requesting the list.
     * @param bool $showMerchantMessage Whether to attach the merchant discount message.
     * @return array The craftable Trinket payload.
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

    /**
     * Build the base eligible-Trinket query for the given Trinketry skill level.
     *
     * @param Skill $trinketrySkill The character's Trinketry skill.
     * @return Builder The base eligible-Trinket query.
     */
    private function buildTrinketItemsQuery(Skill $trinketrySkill): Builder
    {
        return Item::with(['itemPrefix', 'itemSuffix', 'appliedHolyStacks', 'itemSkillProgressions'])
            ->where('type', 'trinket')
            ->where('skill_level_required', '<=', $trinketrySkill->level);
    }

    /**
     * Return the character's current Trinketry skill XP facts.
     *
     * @param Character $character The character requesting the XP facts.
     * @return array The Trinketry XP facts.
     */
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
     * Attempt to craft the item, removing currency, rolling the craft, and giving the item to the player.
     *
     * @param Character $character The character crafting the item.
     * @param Item $item The item being crafted.
     * @return array The updated craftable items and the resulting item preview, when kept.
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
     *
     * @return array|null The crafted item preview, or null when no slot was created.
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
     * @param Character $character The character crafting the item.
     * @param Item $item The item being crafted.
     * @param bool $suppressSuccessServerMessage Whether to suppress the success server message.
     * @param callable|null $destinationCreator The optional retained-destination callback.
     * @return array The batch craft outcome.
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
     *
     * @param Character $character The character requesting the skill.
     * @return Skill The character's Trinketry skill.
     */
    private function fetchCharacterSkill(Character $character): Skill
    {
        return $this->findTrinketrySkill($character);
    }

    /**
     * Resolve the character's Trinketry skill, tolerating its absence.
     *
     * @param Character $character The character being checked.
     * @return Skill|null The character's Trinketry skill, or null when it does not exist.
     */
    public function findTrinketrySkill(Character $character): ?Skill
    {
        $gameSkill = GameSkill::where('name', 'Trinketry')->first();

        if (is_null($gameSkill)) {
            return null;
        }

        return $character->skills()->where('game_skill_id', $gameSkill->id)->first();
    }

    /**
     * Find the character's highest-requirement Trinket that still meaningfully grants XP.
     *
     * @param Character $character The character requesting the target.
     * @return Item|null The resolved meaningful item, or null when none currently applies.
     */
    public function findMeaningfulBatchItem(Character $character): ?Item
    {
        $skill = $this->findTrinketrySkill($character);

        if (is_null($skill) || $skill->level >= $skill->max_level) {
            return null;
        }

        return $this->buildTrinketItemsQuery($skill)
            ->where('skill_level_trivial', '>=', $skill->level)
            ->orderByDesc('skill_level_required')
            ->orderBy('id')
            ->first();
    }

    /**
     * Resolve the Gold Dust and Copper Coin cost facts for crafting the given item.
     *
     * @param Character $character The character being charged.
     * @param Item $item The item being priced.
     * @return array The Gold Dust and Copper Coin cost facts.
     */
    public function craftingCost(Character $character, Item $item): array
    {
        $copperCoinCost = $item->copper_coin_cost;
        $goldDustCost = $item->gold_dust_cost;

        if ($character->classType()->isMerchant()) {
            $copperCoinCost = intdiv($copperCoinCost * 90, 100);
            $goldDustCost = intdiv($goldDustCost * 90, 100);
        }

        return [
            'item_id' => $item->id,
            'item_name' => $item->name,
            'gold_dust' => [
                'required' => $goldDustCost,
                'available' => $character->gold_dust,
                'missing' => max(0, $goldDustCost - $character->gold_dust),
            ],
            'copper_coins' => [
                'required' => $copperCoinCost,
                'available' => $character->copper_coins,
                'missing' => max(0, $copperCoinCost - $character->copper_coins),
            ],
        ];
    }

    /**
     * Determine whether the character can afford the item's Gold Dust and Copper Coin cost.
     *
     * @param Character $character The character being checked.
     * @param Item $item The item being priced.
     * @return bool True when the character can afford the item.
     */
    private function canAfford(Character $character, Item $item): bool
    {
        $cost = $this->craftingCost($character, $item);

        return $cost['gold_dust']['missing'] === 0
            && $cost['copper_coins']['missing'] === 0;
    }

    /**
     * Deduct the item's crafting cost from the character's currencies.
     *
     * @param Character $character The character being charged.
     * @param Item $item The item being crafted.
     * @return void This method does not return a value.
     */
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
     *
     * Kept protected as an existing test seam: TrinketCraftingServiceTest mocks this method to
     * force a deterministic craft roll outcome.
     *
     * @param Skill $trinketSkill The character's Trinketry skill.
     * @return bool True when the craft roll succeeds.
     */
    protected function canCraft(Skill $trinketSkill): bool
    {
        return $this->skillCheckService->characterRoll($trinketSkill) > $this->skillCheckService->getDCCheck($trinketSkill);
    }
}
