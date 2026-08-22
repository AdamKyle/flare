<?php

namespace App\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\Skill;
use App\Game\Automation\BatchCrafting\Enums\CraftSetPosition;
use App\Game\Automation\BatchCrafting\Values\CraftAndEnchantSetPlanEntry;
use App\Game\Character\CharacterInventory\Validations\SetHandsValidation;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\EnchantingService;
use App\Game\Skills\Values\CraftingSkillGroup;
use Illuminate\Support\Collection;

class CraftAndEnchantSetPlanService
{
    /**
     * @param  CraftingService  $craftingService
     * @param  SetHandsValidation  $setHandsValidation
     * @param  EnchantingService  $enchantingService
     */
    public function __construct(
        private readonly CraftingService $craftingService,
        private readonly SetHandsValidation $setHandsValidation,
        private readonly EnchantingService $enchantingService,
    ) {}

    /**
     * Resolve the authoritative Craft and Enchant Set plan for the character's selected positions and enchantments.
     *
     * @param  Character  $character  The character building the plan.
     * @param  array<string, int>  $setPositions  The requested position-to-item-id map.
     * @param  array<string, array{prefix_id: int|null, suffix_id: int|null}>  $enchantments  The requested position-to-enchantment map.
     * @return array{queue: array<int, array>, blockers: array<int, string>, total_crafting_cost: int} The resolved plan, any blockers, and the authoritative total crafting Gold cost.
     */
    public function resolvePlan(Character $character, array $setPositions, array $enchantments): array
    {
        $craftableItemsById = $this->craftingService->findCraftableItemsForAutomation($character, array_values($setPositions));

        $blockers = [];
        $resolvedItems = [];

        foreach (CraftSetPosition::orderedCases() as $position) {
            if ($position->isHandPosition()) {
                continue;
            }

            if (! isset($setPositions[$position->value])) {
                $blockers[] = 'The '.$position->value.' position is required.';

                continue;
            }

            $item = $this->resolvePositionItem($craftableItemsById, $position, $setPositions[$position->value]);

            if (is_null($item)) {
                $blockers[] = 'The selected item for the '.$position->value.' position is no longer craftable.';

                continue;
            }

            $resolvedItems[$position->value] = $item;
        }

        $handResult = $this->resolveHandPositions($craftableItemsById, $setPositions);
        $blockers = [...$blockers, ...$handResult['blockers']];
        $resolvedItems = [...$resolvedItems, ...$handResult['items']];

        $enchantResult = $this->resolveEnchantments($character, $resolvedItems, $enchantments);
        $blockers = [...$blockers, ...$enchantResult['blockers']];

        $queue = array_map(
            fn (CraftAndEnchantSetPlanEntry $entry): array => $entry->toArray(),
            $this->buildQueue($resolvedItems, $enchantResult['entries']),
        );

        return [
            'queue' => $queue,
            'blockers' => $blockers,
            'total_crafting_cost' => $this->totalCraftingCost($character, $resolvedItems),
        ];
    }

    /**
     * Calculate the authoritative total crafting Gold cost for every resolved item in the plan.
     *
     * @param  Character  $character  The character building the plan.
     * @param  array<string, Item>  $resolvedItems  The resolved items keyed by position value.
     * @return int The authoritative total crafting Gold cost.
     */
    private function totalCraftingCost(Character $character, array $resolvedItems): int
    {
        return array_sum(array_map(
            fn (Item $item): int => $this->craftingService->getItemCostForAutomation($character, $item),
            $resolvedItems,
        ));
    }

    /**
     * Resolve the optional hand positions into resolved items, validating the hand combination.
     *
     * @param  Collection<int, Item>  $craftableItemsById  The character's bulk-resolved craftable items, keyed by item id.
     * @param  array<string, int>  $setPositions  The requested position-to-item-id map.
     * @return array{items: array<string, Item>, blockers: array<int, string>} The resolved hand items and any blockers.
     */
    private function resolveHandPositions(Collection $craftableItemsById, array $setPositions): array
    {
        $handPositions = array_filter(
            CraftSetPosition::orderedCases(),
            fn (CraftSetPosition $position): bool => $position->isHandPosition(),
        );

        $handItems = new Collection;
        $resolvedItems = [];

        foreach ($handPositions as $position) {
            if (! isset($setPositions[$position->value])) {
                continue;
            }

            $item = $this->resolveHandItem($craftableItemsById, $setPositions[$position->value]);

            if (is_null($item)) {
                return ['items' => [], 'blockers' => ['The selected item for the '.$position->value.' position is not a valid hand item.']];
            }

            $handItems->push($item);
            $resolvedItems[$position->value] = $item;
        }

        if (! $this->setHandsValidation->areHandItemsValid($handItems)) {
            return ['items' => [], 'blockers' => ['The selected hand items are not a valid combination.']];
        }

        return ['items' => $resolvedItems, 'blockers' => []];
    }

    /**
     * Resolve every included position's requested enchantment, in bounded queries.
     *
     * Every requested Prefix/Suffix id across every included position is loaded in a single
     * bounded query before any per-position validation happens, so the loop below performs
     * no database query of its own.
     *
     * @param  Character  $character  The character building the plan.
     * @param  array<string, Item>  $resolvedItems  The resolved items keyed by position value.
     * @param  array<string, array{prefix_id: int|null, suffix_id: int|null}>  $enchantments  The requested position-to-enchantment map.
     * @return array{entries: array<string, array{prefix_id: int|null, suffix_id: int|null}>, blockers: array<int, string>} The resolved enchantments and any blockers.
     */
    private function resolveEnchantments(Character $character, array $resolvedItems, array $enchantments): array
    {
        $affixesById = ItemAffix::whereIn('id', $this->requestedAffixIds($resolvedItems, $enchantments))
            ->where('randomly_generated', false)
            ->get()
            ->keyBy('id');

        $enchantingSkill = $this->enchantingService->findEnchantingSkill($character);
        $characterInt = $character->getInformation()->statMod('int');

        $entries = [];
        $blockers = [];

        foreach ($resolvedItems as $positionValue => $item) {
            $requested = $enchantments[$positionValue] ?? [];
            $prefixId = $requested['prefix_id'] ?? null;
            $suffixId = $requested['suffix_id'] ?? null;

            if (is_null($prefixId) && is_null($suffixId)) {
                $blockers[] = 'The '.$positionValue.' position requires at least one Prefix or Suffix.';

                continue;
            }

            $resolved = $this->resolvePositionEnchantment($affixesById, $enchantingSkill, $characterInt, $prefixId, $suffixId, $positionValue);

            if (is_null($resolved)) {
                $blockers[] = 'The selected enchantment for the '.$positionValue.' position is not currently valid.';

                continue;
            }

            $entries[$positionValue] = $resolved;
        }

        return ['entries' => $entries, 'blockers' => $blockers];
    }

    /**
     * Collect every distinct requested Prefix/Suffix affix id across every included position.
     *
     * @param  array<string, Item>  $resolvedItems  The resolved items keyed by position value.
     * @param  array<string, array{prefix_id: int|null, suffix_id: int|null}>  $enchantments  The requested position-to-enchantment map.
     * @return array<int, int> The distinct requested affix ids.
     */
    private function requestedAffixIds(array $resolvedItems, array $enchantments): array
    {
        $ids = [];

        foreach (array_keys($resolvedItems) as $positionValue) {
            $requested = $enchantments[$positionValue] ?? [];
            $ids[] = $requested['prefix_id'] ?? null;
            $ids[] = $requested['suffix_id'] ?? null;
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * Validate one position's requested enchantment against the preloaded affix map, in memory.
     *
     * @param  Collection<int, ItemAffix>  $affixesById  The preloaded requested affixes, keyed by id.
     * @param  Skill|null  $enchantingSkill  The character's already-resolved Enchanting skill, when present.
     * @param  int  $characterInt  The character's already-resolved Intelligence stat.
     * @param  int|null  $prefixId  The requested Prefix affix id, when selected.
     * @param  int|null  $suffixId  The requested Suffix affix id, when selected.
     * @param  string  $positionValue  The plan position value being validated, used only for blocker text.
     * @return array{prefix_id: int|null, suffix_id: int|null}|null The resolved enchantment, or null when invalid.
     */
    private function resolvePositionEnchantment(Collection $affixesById, ?Skill $enchantingSkill, int $characterInt, ?int $prefixId, ?int $suffixId, string $positionValue): ?array
    {
        $prefix = is_null($prefixId) ? null : $this->validateAffix($affixesById, $prefixId, 'prefix', $enchantingSkill);

        if (! is_null($prefixId) && is_null($prefix)) {
            return null;
        }

        $suffix = is_null($suffixId) ? null : $this->validateAffix($affixesById, $suffixId, 'suffix', $enchantingSkill);

        if (! is_null($suffixId) && is_null($suffix)) {
            return null;
        }

        if ((! is_null($prefix) && $characterInt < $prefix->int_required) || (! is_null($suffix) && $characterInt < $suffix->int_required)) {
            return null;
        }

        return ['prefix_id' => $prefix?->id, 'suffix_id' => $suffix?->id];
    }

    /**
     * Validate a preloaded candidate affix against its required type and the character's Enchanting skill level.
     *
     * @param  Collection<int, ItemAffix>  $affixesById  The preloaded requested affixes, keyed by id.
     * @param  int  $affixId  The requested affix id.
     * @param  string  $type  The required affix type (prefix or suffix).
     * @param  Skill|null  $enchantingSkill  The character's already-resolved Enchanting skill, when present.
     * @return ItemAffix|null The validated affix, or null when invalid or currently ineligible.
     */
    private function validateAffix(Collection $affixesById, int $affixId, string $type, ?Skill $enchantingSkill): ?ItemAffix
    {
        $affix = $affixesById->get($affixId);

        if (is_null($affix) || $affix->type !== $type || is_null($enchantingSkill)) {
            return null;
        }

        if ($enchantingSkill->level < $affix->skill_level_required) {
            return null;
        }

        return $affix;
    }

    /**
     * Resolve the currently craftable item for a required plan position and requested item id.
     *
     * @param  Collection<int, Item>  $craftableItemsById  The character's bulk-resolved craftable items, keyed by item id.
     * @param  CraftSetPosition  $position  The plan position being resolved.
     * @param  int  $itemId  The requested item id for the position.
     * @return Item|null The matching craftable item, or null when unavailable.
     */
    private function resolvePositionItem(Collection $craftableItemsById, CraftSetPosition $position, int $itemId): ?Item
    {
        $item = $craftableItemsById->get($itemId);

        if (is_null($item) || $item->type !== $position->requiredItemType()) {
            return null;
        }

        return $item;
    }

    /**
     * Resolve the currently craftable hand item (weapon or shield) for a requested item id.
     *
     * @param  Collection<int, Item>  $craftableItemsById  The character's bulk-resolved craftable items, keyed by item id.
     * @param  int  $itemId  The requested item id for the hand position.
     * @return Item|null The matching craftable hand item, or null when unavailable.
     */
    private function resolveHandItem(Collection $craftableItemsById, int $itemId): ?Item
    {
        $item = $craftableItemsById->get($itemId);

        if (is_null($item) || ! $this->setHandsValidation->isHandItem($item)) {
            return null;
        }

        return $item;
    }

    /**
     * Build the final plan queue in the single authoritative Craft Set position order.
     *
     * @param  array<string, Item>  $resolvedItems  The resolved items keyed by position value.
     * @param  array<string, array{prefix_id: int|null, suffix_id: int|null}>  $enchantEntries  The resolved enchantments keyed by position value.
     * @return array<int, CraftAndEnchantSetPlanEntry> The ordered queue entries.
     */
    private function buildQueue(array $resolvedItems, array $enchantEntries): array
    {
        $queue = [];

        foreach (CraftSetPosition::orderedCases() as $position) {
            if (! isset($resolvedItems[$position->value]) || ! isset($enchantEntries[$position->value])) {
                continue;
            }

            $item = $resolvedItems[$position->value];
            $enchantment = $enchantEntries[$position->value];

            $queue[] = new CraftAndEnchantSetPlanEntry(
                $position,
                $item->id,
                $position->craftingGroup()?->value ?? $this->resolveHandCraftingType($item),
                $item->affix_name ?? $item->name,
                $enchantment['prefix_id'],
                $enchantment['suffix_id'],
            );
        }

        return $queue;
    }

    /**
     * Resolve the crafting type used to craft a resolved hand item (weapon or shield).
     *
     * @param  Item  $item  The resolved hand item.
     * @return string The crafting type used to craft the hand item.
     */
    private function resolveHandCraftingType(Item $item): string
    {
        return $this->setHandsValidation->handedness($item) === 'shield' ? CraftingSkillGroup::ARMOUR->value : CraftingSkillGroup::WEAPON->value;
    }
}
