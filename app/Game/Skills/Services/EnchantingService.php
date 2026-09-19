<?php

namespace App\Game\Skills\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\GlobalEventCraftingInventory;
use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\Skill;
use App\Flare\Pagination\Pagination;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Events\Services\GlobalEventGoalEligibilityService;
use App\Game\Messages\Types\CraftingMessageTypes;
use App\Game\Npcs\Actions\QueenOfHearts\Services\RandomEnchantmentService;
use App\Game\Skills\Events\UpdateSkillEvent;
use App\Game\Skills\Handlers\HandleUpdatingEnchantingGlobalEventGoal;
use App\Game\Skills\Services\Traits\UpdateCharacterCurrency;
use App\Game\Skills\Transformers\EnchantingAffixTransformer;
use App\Game\Skills\Transformers\EnchantingItemTransformer;
use App\Game\Skills\Transformers\EventEnchantingItemTransformer;
use App\Game\Skills\Values\SkillTypeValue;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EnchantingService
{
    use ResponseBuilder, UpdateCharacterCurrency;

    private CharacterStatBuilder $characterStatBuilder;

    private EnchantItemService $enchantItemService;

    private RandomEnchantmentService $randomEnchantmentService;

    private HandleUpdatingEnchantingGlobalEventGoal $handleUpdatingCraftingGlobalEventGoal;

    private GlobalEventGoalEligibilityService $globalEventGoalEligibilityService;

    private Pagination $pagination;

    private bool $sentToEasyMessage = false;

    /**
     * Only set if the affix to be applied was too easy to enchant.
     */
    private bool $wasTooEasy = false;

    /**
     * @param CharacterStatBuilder $characterStatBuilder
     * @param EnchantItemService $enchantItemService
     * @param RandomEnchantmentService $randomEnchantmentService
     * @param GlobalEventGoalEligibilityService $globalEventGoalEligibilityService
     * @param Pagination $pagination
     * @param EnchantingItemTransformer $enchantingItemTransformer
     * @param EventEnchantingItemTransformer $eventEnchantingItemTransformer
     * @param EnchantingAffixTransformer $enchantingAffixTransformer
     * @param EnchantingAffixService $enchantingAffixService
     */
    public function __construct(
        CharacterStatBuilder $characterStatBuilder,
        EnchantItemService $enchantItemService,
        RandomEnchantmentService $randomEnchantmentService,
        GlobalEventGoalEligibilityService $globalEventGoalEligibilityService,
        Pagination $pagination,
        private readonly EnchantingItemTransformer $enchantingItemTransformer,
        private readonly EventEnchantingItemTransformer $eventEnchantingItemTransformer,
        private readonly EnchantingAffixTransformer $enchantingAffixTransformer,
        private readonly EnchantingAffixService $enchantingAffixService,
    ) {

        $this->characterStatBuilder = $characterStatBuilder;
        $this->enchantItemService = $enchantItemService;
        $this->randomEnchantmentService = $randomEnchantmentService;
        $this->globalEventGoalEligibilityService = $globalEventGoalEligibilityService;
        $this->pagination = $pagination;
    }

    /**
     * Fetches a paginated list of items eligible for enchanting for the given source.
     *
     * @param Character $character
     * @param string $source
     * @param int $perPage
     * @param int $page
     * @param string $search
     * @return array
     */
    public function fetchPaginatedItems(Character $character, string $source, int $perPage, int $page, string $search = ''): array
    {
        if ($source === 'event') {
            return $this->fetchPaginatedEventItems($character, $perPage, $page, $search);
        }

        $inventory = Inventory::where('character_id', $character->id)->first();

        $query = InventorySlot::with(['item.itemPrefix', 'item.itemSuffix', 'item.appliedHolyStacks', 'item.itemSkillProgressions'])
            ->where('inventory_id', $inventory->id)
            ->where('equipped', false)
            ->whereHas('item', function ($itemQuery) {
                $itemQuery->whereNotIn('type', ['quest', 'alchemy', 'gem', 'trinket', 'artifact']);
            });

        $this->applyItemSearch($query, $search);

        $paginator = $query->orderBy('id')->paginate($perPage, ['*'], 'page', $page);

        return $this->pagination->transformLengthAwarePaginator($paginator, $this->enchantingItemTransformer);
    }

    /**
     * Fetches a paginated list of available affixes of the given type for the character.
     *
     * @param Character $character
     * @param string $type
     * @param int $perPage
     * @param int $page
     * @param string $search
     * @return array
     */
    public function fetchPaginatedAffixes(Character $character, string $type, int $perPage, int $page, string $search = ''): array
    {
        $enchantingSkill = $this->getEnchantingSkill($character);

        $query = ItemAffix::where('skill_level_required', '<=', $enchantingSkill->level)
            ->where('randomly_generated', false)
            ->where('type', $type);

        if ($search !== '') {
            $query->where('name', 'LIKE', '%'.$search.'%');
        }

        $paginator = $query->orderBy('skill_level_required', 'asc')
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return $this->pagination->transformLengthAwarePaginator($paginator, $this->enchantingAffixTransformer);
    }

    /**
     * Fetch the paginated Global Event Crafting Inventory items eligible for enchanting.
     *
     * @param Character $character
     * @param int $perPage
     * @param int $page
     * @param string $search
     * @return array
     */
    private function fetchPaginatedEventItems(Character $character, int $perPage, int $page, string $search): array
    {
        $globalEventGoal = $this->globalEventGoalEligibilityService->currentEnchantingGoalFor($character);

        if (is_null($globalEventGoal)) {
            return $this->pagination->paginateCollectionResponse(new Collection, $perPage, $page);
        }

        $eventInventory = GlobalEventCraftingInventory::where('character_id', $character->id)
            ->where('global_event_goal_id', $globalEventGoal->id)
            ->first();

        if (is_null($eventInventory)) {
            return $this->pagination->paginateCollectionResponse(new Collection, $perPage, $page);
        }

        $query = GlobalEventCraftingInventorySlot::with(['item.itemPrefix', 'item.itemSuffix', 'item.appliedHolyStacks', 'item.itemSkillProgressions'])
            ->where('global_event_crafting_inventory_id', $eventInventory->id)
            ->whereHas('item', function ($itemQuery) {
                $itemQuery->whereNotIn('type', ['quest', 'alchemy', 'gem', 'trinket', 'artifact']);
            });

        $this->applyItemSearch($query, $search);

        $paginator = $query->orderBy('id')->paginate($perPage, ['*'], 'page', $page);

        return $this->pagination->transformLengthAwarePaginator($paginator, $this->eventEnchantingItemTransformer);
    }

    /**
     * Applies a name/prefix/suffix search to an item-bearing query.
     *
     * @param Builder $query
     * @param string $search
     * @return void
     */
    private function applyItemSearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $query->whereHas('item', function ($itemQuery) use ($search) {
            $itemQuery->where('name', 'LIKE', '%'.$search.'%')
                ->orWhereHas('itemPrefix', fn ($prefixQuery) => $prefixQuery->where('name', 'LIKE', '%'.$search.'%'))
                ->orWhereHas('itemSuffix', fn ($suffixQuery) => $suffixQuery->where('name', 'LIKE', '%'.$search.'%'));
        });
    }

    /**
     * Fetches the affixes for a character.
     *
     * Only returns that which the player has the skill level and intelligence for.
     *
     * @param Character $character
     * @param bool $ignoreTrinkets
     * @param bool $showMerchantMessage
     * @return array
     */
    public function fetchAffixes(Character $character, bool $ignoreTrinkets = false, bool $showMerchantMessage = true): array
    {
        return $this->enchantingAffixService->fetchAffixes($character, $ignoreTrinkets, $showMerchantMessage);
    }

    /**
     * Get the current state of the enchanting xp bar.
     *
     * @param Character $character
     * @return array
     */
    public function getEnchantingXP(Character $character): array
    {
        $skill = $this->getEnchantingSkill($character);

        return [
            'current_xp' => $skill->xp,
            'next_level_xp' => $skill->xp_max,
            'skill_name' => $skill->name,
            'level' => $skill->level,
        ];
    }

    /**
     * Does the cost supplied actually match the actual cost?
     *
     * @param Character $character
     * @param array $enchantmentIds
     * @param int $itemId
     * @return int
     */
    public function getCostOfEnchantment(Character $character, array $enchantmentIds, int $itemId): int
    {
        $itemAffixes = ItemAffix::findMany($enchantmentIds);
        $itemToEnchant = Item::find($itemId);

        if ($itemAffixes->isEmpty()) {
            return 0;
        }

        if (is_null($itemToEnchant)) {
            return 0;
        }

        $cost = $itemAffixes->sum('cost');

        foreach ($itemAffixes as $itemAffix) {
            if (! is_null($itemToEnchant->{'item_'.$itemAffix->type.'_id'})) {
                $cost += 1000;
            }
        }

        if ($character->classType()->isMerchant()) {
            $cost = floor($cost - $cost * 0.15);

            ServerMessageHandler::sendBasicMessage($character->user, 'As a Merchant you get a 15% reduction on enchanting items (reduction applied to total price).');
        }

        return $cost;
    }

    /**
     * Enchant an item.
     *
     * Attempts to enchant an item with the supplied affixes and slot. The params passed in
     * must be the request params coming back from the request.
     *
     * @param Character $character
     * @param array $params
     * @param InventorySlot|GlobalEventCraftingInventorySlot $slot
     * @param int $cost
     * @return bool
     */
    public function enchant(Character $character, array $params, InventorySlot|GlobalEventCraftingInventorySlot $slot, int $cost): bool
    {
        $enchantingSkill = $this->getEnchantingSkill($character);

        $character->update([
            'gold' => $character->gold - $cost,
        ]);

        $character = $character->refresh();

        $enchantSucceeded = $this->attachAffixes($params['affix_ids'], $slot, $enchantingSkill, $character);

        $this->enchantItemService->updateSlot($slot, $params['enchant_for_event']);

        return $enchantSucceeded;
    }

    /**
     * Enchant an item directly for Batch Crafting, with no InventorySlot involved.
     *
     * Applies the given affixes to a clone of the item after validating the full
     * affix list and gold cost. Returns the final item on success, or a destroyed
     * result if the roll fails.
     *
     * $suppressSuccessServerMessage skips only the "Applied enchantment: X to: Y"
     * success message, used when the batch processor will emit a linked equivalent
     * once the item is committed to the Crafted Items Set. Failure messages are
     * never suppressed.
     *
     * @param Character $character
     * @param Item $item
     * @param array $affixIds
     * @param int $cost
     * @param bool $suppressSuccessServerMessage
     * @return array
     */
    public function enchantItemForBatch(Character $character, Item $item, array $affixIds, int $cost, bool $suppressSuccessServerMessage = false): array
    {
        $enchantingSkill = $this->getEnchantingSkill($character);
        $characterInt = $character->getInformation()->statMod('int');
        $affixes = [];

        foreach (array_values(array_filter($affixIds, fn ($affixId) => ! is_null($affixId))) as $affixId) {
            $affix = ItemAffix::find($affixId);

            if (is_null($affix)) {
                return ['success' => false, 'item' => null, 'reason' => 'invalid_affix'];
            }

            if (! in_array($affix->type, ['prefix', 'suffix'], true)) {
                return ['success' => false, 'item' => null, 'reason' => 'invalid_affix_type'];
            }

            if ($enchantingSkill->level < $affix->skill_level_required) {
                ServerMessageHandler::handleMessage($character->user, CraftingMessageTypes::TO_HARD_TO_CRAFT);

                return ['success' => false, 'item' => null, 'reason' => 'skill_too_low'];
            }

            if ($characterInt < $affix->int_required) {
                ServerMessageHandler::handleMessage($character->user, CraftingMessageTypes::INT_TO_LOW_ENCHANTING);

                return ['success' => false, 'item' => null, 'reason' => 'int_too_low'];
            }

            $affixes[] = $affix;
        }

        if (empty($affixes)) {
            return ['success' => false, 'item' => null, 'reason' => 'no_affixes'];
        }

        if ($character->gold < $cost) {
            return ['success' => false, 'item' => null, 'reason' => 'not_enough_gold'];
        }

        $character->update([
            'gold' => $character->gold - $cost,
        ]);

        $character = $character->refresh();

        foreach ($affixes as $affix) {

            $tooEasy = $enchantingSkill->level > $affix->skill_level_trivial;

            if ($tooEasy) {
                ServerMessageHandler::handleMessage($character->user, CraftingMessageTypes::TO_EASY_TO_CRAFT);
            }

            if (! $this->enchantItemService->attachAffix($item, $affix, $enchantingSkill, $tooEasy)) {
                ServerMessageHandler::handleMessage(
                    $character->user,
                    CraftingMessageTypes::ENCHANTMENT_FAILED,
                    'You failed to apply '.$affix->name.' to: '.$item->refresh()->affix_name.'. The item shatters before you. You lost the investment.'
                );

                $this->enchantItemService->discardPendingItem();

                return ['success' => false, 'item' => null, 'reason' => 'destroyed'];
            }

            if (! $suppressSuccessServerMessage) {
                ServerMessageHandler::handleMessage($character->user, CraftingMessageTypes::ENCHANTED, 'Applied enchantment: '.$affix->name.' to: '.$item->refresh()->affix_name);
            }

            if (! $tooEasy) {
                event(new UpdateSkillEvent($enchantingSkill));
            }
        }

        return ['success' => true, 'item' => $this->enchantItemService->finalizeBatchItem(), 'reason' => null];
    }

    /**
     * Resolve an exact requested Prefix/Suffix affix pair for Batch Crafting, without any fallback.
     *
     * Validates that at least one affix id is present, that each requested id resolves to a real
     * non-random affix of the correct type, and that the character's Enchanting skill level meets
     * each requested affix's requirement. Never substitutes a different affix than the one requested.
     *
     * @param Character $character
     * @param int|null $prefixId
     * @param int|null $suffixId
     * @return array
     */
    public function resolveBatchAffixes(Character $character, ?int $prefixId, ?int $suffixId): array
    {
        if (is_null($prefixId) && is_null($suffixId)) {
            return ['prefix' => null, 'suffix' => null, 'error' => 'no_affixes'];
        }

        $enchantingSkill = $this->getEnchantingSkill($character);

        $prefix = null;
        $suffix = null;

        if (! is_null($prefixId)) {
            $prefix = $this->resolveExactBatchAffix($prefixId, 'prefix', $enchantingSkill);

            if (is_null($prefix)) {
                return ['prefix' => null, 'suffix' => null, 'error' => 'invalid_prefix'];
            }
        }

        if (! is_null($suffixId)) {
            $suffix = $this->resolveExactBatchAffix($suffixId, 'suffix', $enchantingSkill);

            if (is_null($suffix)) {
                return ['prefix' => null, 'suffix' => null, 'error' => 'invalid_suffix'];
            }
        }

        return ['prefix' => $prefix, 'suffix' => $suffix, 'error' => null];
    }

    /**
     * Resolve the strongest meaningful Prefix/Suffix affix pair for Craft and Enchant For Experience.
     *
     * Independently resolves the highest requirement non-trivial Prefix and Suffix the character's
     * current Enchanting skill level can meaningfully learn from. Never selects a weaker affix
     * because Intelligence is insufficient for the resolved combination.
     *
     * @param Character $character
     * @return array
     */
    public function findMeaningfulBatchAffixes(Character $character): array
    {
        $enchantingSkill = $this->getEnchantingSkill($character);

        $prefix = $this->findMeaningfulAffix('prefix', $enchantingSkill);
        $suffix = $this->findMeaningfulAffix('suffix', $enchantingSkill);

        return [
            'prefix' => $prefix,
            'suffix' => $suffix,
            'skill' => $enchantingSkill,
            'intelligence_blocked' => $this->isIntelligenceBlocked($character, $prefix, $suffix),
        ];
    }

    /**
     * Resolve the cheapest affordable Prefix/Suffix affix pair for Enchant For Event.
     *
     * Independently resolves the cheapest eligible, affordable Prefix and Suffix the character's
     * current Enchanting skill level and Gold allow. Never selects a weaker affix because
     * Intelligence is insufficient for the resolved combination.
     *
     * @param Character $character
     * @return array
     */
    public function findEventBatchAffixes(Character $character): array
    {
        $enchantingSkill = $this->getEnchantingSkill($character);

        $prefix = $this->findAffordableAffix('prefix', $enchantingSkill, $character->gold);
        $suffix = $this->findAffordableAffix('suffix', $enchantingSkill, $character->gold);

        return [
            'prefix' => $prefix,
            'suffix' => $suffix,
            'intelligence_blocked' => $this->isIntelligenceBlocked($character, $prefix, $suffix),
        ];
    }

    /**
     * Resolve one exact requested Batch Crafting affix by id, type, and skill level requirement.
     *
     * @param int $affixId
     * @param string $type
     * @param Skill $enchantingSkill
     * @return ItemAffix|null
     */
    private function resolveExactBatchAffix(int $affixId, string $type, Skill $enchantingSkill): ?ItemAffix
    {
        $affix = ItemAffix::where('id', $affixId)
            ->where('type', $type)
            ->where('randomly_generated', false)
            ->first();

        if (is_null($affix) || $enchantingSkill->level < $affix->skill_level_required) {
            return null;
        }

        return $affix;
    }

    /**
     * Resolve the strongest non-trivial affix of the given type the Enchanting skill can meaningfully learn from.
     *
     * @param string $type
     * @param Skill $enchantingSkill
     * @return ItemAffix|null
     */
    private function findMeaningfulAffix(string $type, Skill $enchantingSkill): ?ItemAffix
    {
        return ItemAffix::where('type', $type)
            ->where('randomly_generated', false)
            ->where('skill_level_required', '<=', $enchantingSkill->level)
            ->where('skill_level_trivial', '>=', $enchantingSkill->level)
            ->orderByDesc('skill_level_required')
            ->orderBy('id')
            ->first();
    }

    /**
     * Resolve the cheapest eligible affix of the given type the character can currently afford.
     *
     * @param string $type
     * @param Skill $enchantingSkill
     * @param int $availableGold
     * @return ItemAffix|null
     */
    private function findAffordableAffix(string $type, Skill $enchantingSkill, int $availableGold): ?ItemAffix
    {
        return ItemAffix::where('type', $type)
            ->where('randomly_generated', false)
            ->where('skill_level_required', '<=', $enchantingSkill->level)
            ->where('cost', '<=', $availableGold)
            ->orderBy('cost')
            ->orderBy('id')
            ->first();
    }

    /**
     * Determine whether the character's Intelligence blocks the resolved affix combination.
     *
     * @param Character $character
     * @param ItemAffix|null $prefix
     * @param ItemAffix|null $suffix
     * @return bool
     */
    private function isIntelligenceBlocked(Character $character, ?ItemAffix $prefix, ?ItemAffix $suffix): bool
    {
        if (is_null($prefix) && is_null($suffix)) {
            return false;
        }

        $characterInt = $character->getInformation()->statMod('int');

        return (! is_null($prefix) && $characterInt < $prefix->int_required)
            || (! is_null($suffix) && $characterInt < $suffix->int_required);
    }

    /**
     * Resolve the crafting-time multiplier label for an item's currently applied affixes.
     *
     * @param Item $item
     * @return string|null
     */
    public function timeForEnchanting(Item $item): ?string
    {
        if (! is_null($item->itemPrefix) && ! is_null($item->itemSuffix)) {
            return 'triple';
        }

        if (! is_null($item->itemPrefix) || ! is_null($item->itemSuffix)) {
            return 'double';
        }

        return null;
    }

    /**
     * Resolve the target slot for enchanting from the character's normal Inventory or their Global Event Crafting Inventory.
     *
     * @param Character $character
     * @param int $slotId
     * @return InventorySlot|GlobalEventCraftingInventorySlot|null
     */
    public function getSlotFromInventory(Character $character, int $slotId): InventorySlot|GlobalEventCraftingInventorySlot|null
    {
        $inventory = Inventory::where('character_id', $character->id)->first();

        $foundInInventory = InventorySlot::where('id', $slotId)->where('inventory_id', $inventory->id)->where('equipped', false)->first();

        if (is_null($foundInInventory)) {
            $globalEventGoal = $this->globalEventGoalEligibilityService->currentEnchantingGoalFor($character);

            if (is_null($globalEventGoal)) {
                return null;
            }

            $inventory = GlobalEventCraftingInventory::where('character_id', $character->id)
                ->where('global_event_goal_id', $globalEventGoal->id)
                ->first();

            if (is_null($inventory)) {
                return null;
            }

            $foundInInventory = GlobalEventCraftingInventorySlot::where('id', $slotId)->where('global_event_crafting_inventory_id', $inventory->id)->first();
        }

        return $foundInInventory;
    }

    /**
     * Resolve the character's Enchanting skill, tolerating its absence.
     *
     * @param Character $character
     * @return Skill|null
     */
    public function findEnchantingSkill(Character $character): ?Skill
    {
        $gameSkill = GameSkill::where('type', SkillTypeValue::ENCHANTING->value)->first();

        if (is_null($gameSkill)) {
            return null;
        }

        return Skill::where('character_id', $character->id)->where('game_skill_id', $gameSkill->id)->first();
    }

    /**
     * Resolve the character's Enchanting skill, assuming it exists.
     *
     * @param Character $character
     * @return Skill
     */
    private function getEnchantingSkill(Character $character): Skill
    {
        $gameSkill = GameSkill::where('type', SkillTypeValue::ENCHANTING->value)->first();

        return Skill::where('character_id', $character->id)->where('game_skill_id', $gameSkill->id)->first();
    }

    /**
     * Attach every requested affix to the target slot's item in order, stopping at the first failure.
     *
     * @param array $affixes
     * @param InventorySlot|GlobalEventCraftingInventorySlot $slot
     * @param Skill $enchantingSkill
     * @param Character $character
     * @return bool
     */
    private function attachAffixes(array $affixes, InventorySlot|GlobalEventCraftingInventorySlot $slot, Skill $enchantingSkill, Character $character): bool
    {
        foreach ($affixes as $affixId) {
            $slot = $slot->refresh();

            $affix = ItemAffix::find($affixId);

            if (is_null($affix)) {
                continue;
            }

            // Reset.
            $this->wasTooEasy = false;

            if ($enchantingSkill->level < $affix->skill_level_required) {
                ServerMessageHandler::handleMessage($character->user, CraftingMessageTypes::TO_HARD_TO_CRAFT);

                return false;
            }

            if ($character->getInformation()->statMod('int') < $affix->int_required) {
                ServerMessageHandler::handleMessage($character->user, CraftingMessageTypes::INT_TO_LOW_ENCHANTING);

                return false;
            }

            if ($enchantingSkill->level > $affix->skill_level_trivial) {
                if (! $this->sentToEasyMessage) {
                    ServerMessageHandler::handleMessage($character->user, CraftingMessageTypes::TO_EASY_TO_CRAFT);

                    $this->sentToEasyMessage = true;
                }
                $this->processedEnchant($slot, $affix, $character, $enchantingSkill, true);

                $this->wasTooEasy = true;
            }

            /**
             * If the affix wasn't too easy to attach, attempt to enchant with the difficulty check
             * in place.
             *
             * If we fail to do this then we return from the loop.
             */
            if (! $this->wasTooEasy) {
                if (! $this->processedEnchant($slot, $affix, $character, $enchantingSkill)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Attempt to attach one affix to the target slot's item and record the outcome.
     *
     * @param InventorySlot|GlobalEventCraftingInventorySlot $slot
     * @param ItemAffix $affix
     * @param Character $character
     * @param Skill $enchantingSkill
     * @param bool $tooEasy
     * @return bool
     */
    private function processedEnchant(InventorySlot|GlobalEventCraftingInventorySlot $slot, ItemAffix $affix, Character $character, Skill $enchantingSkill, bool $tooEasy = false): bool
    {
        $enchanted = $this->enchantItemService->attachAffix($slot->item, $affix, $enchantingSkill, $tooEasy);

        if ($enchanted) {
            $this->appliedEnchantment($slot, $affix, $character, $enchantingSkill, $tooEasy);
        } else {
            $this->failedToApplyEnchantment($slot, $affix, $character);

            return false;
        }

        return true;
    }

    /**
     * Send the applied-enchantment server message and award Enchanting skill XP when applicable.
     *
     * @param InventorySlot|GlobalEventCraftingInventorySlot $slot
     * @param ItemAffix $affix
     * @param Character $character
     * @param Skill $enchantingSkill
     * @param bool $tooEasy
     * @return void
     */
    private function appliedEnchantment(InventorySlot|GlobalEventCraftingInventorySlot $slot, ItemAffix $affix, Character $character, Skill $enchantingSkill, bool $tooEasy = false): void
    {
        $message = 'Applied enchantment: '.$affix->name.' to: '.$slot->item->refresh()->affix_name;

        ServerMessageHandler::handleMessage($character->user, CraftingMessageTypes::ENCHANTED, $message, $slot->id);

        if (! $tooEasy) {
            event(new UpdateSkillEvent($enchantingSkill));
        }
    }

    /**
     * Send the failed-enchantment server message and delete the shattered slot.
     *
     * @param InventorySlot|GlobalEventCraftingInventorySlot $slot
     * @param ItemAffix $affix
     * @param Character $character
     * @return void
     */
    private function failedToApplyEnchantment(InventorySlot|GlobalEventCraftingInventorySlot $slot, ItemAffix $affix, Character $character): void
    {
        $message = 'You failed to apply '.$affix->name.' to: '.$slot->item->refresh()->affix_name.'. The item shatters before you. You lost the investment.';

        ServerMessageHandler::handleMessage($character->user, CraftingMessageTypes::ENCHANTMENT_FAILED, $message);

        $this->enchantItemService->deleteSlot($slot);

        if ($slot instanceof InventorySlot) {
            event(new UpdateCharacterInventoryCountEvent($character));
        }
    }
}
