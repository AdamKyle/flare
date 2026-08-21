<?php

namespace App\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Automation\BatchCrafting\Enums\CraftSetPosition;
use App\Game\Automation\BatchCrafting\Values\CraftSetPlanEntry;
use App\Game\Character\CharacterInventory\Validations\SetHandsValidation;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Values\CraftingSkillGroup;
use Illuminate\Support\Collection;

class CraftSetPlanService
{
    /**
     * @param  CraftingService  $craftingService
     * @param  SetHandsValidation  $setHandsValidation
     */
    public function __construct(
        private readonly CraftingService $craftingService,
        private readonly SetHandsValidation $setHandsValidation,
    ) {}

    /**
     * Return the full authoritative Craft Set plan position sequence.
     *
     * @return array<int, string> The authoritative plan position order.
     */
    public function positions(): array
    {
        return array_map(
            fn (CraftSetPosition $position): string => $position->value,
            CraftSetPosition::orderedCases(),
        );
    }

    /**
     * Resolve the authoritative Craft Set plan for the character's selected positions.
     *
     * @param  Character  $character  The character building the plan.
     * @param  array<string, int>  $setPositions  The requested position-to-item-id map.
     * @return array{queue: array<int, array{position: string, item_id: int, crafting_type: string, item_name: string}>, blockers: array<int, string>, total_cost: int} The resolved plan, any blockers, and the authoritative total Gold cost.
     */
    public function resolvePlan(Character $character, array $setPositions): array
    {
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

            $item = $this->resolvePositionItem($character, $position, $setPositions[$position->value]);

            if (is_null($item)) {
                $blockers[] = 'The selected item for the '.$position->value.' position is no longer craftable.';

                continue;
            }

            $resolvedItems[$position->value] = $item;
        }

        $handResult = $this->resolveHandPositions($character, $setPositions);
        $blockers = [...$blockers, ...$handResult['blockers']];
        $resolvedItems = [...$resolvedItems, ...$handResult['items']];

        $queue = array_map(
            fn (CraftSetPlanEntry $entry): array => $entry->toArray(),
            $this->buildQueue($resolvedItems),
        );

        return [
            'queue' => $queue,
            'blockers' => $blockers,
            'total_cost' => $this->totalCost($character, $resolvedItems),
        ];
    }

    /**
     * Calculate the authoritative total Gold cost to craft every resolved item in the plan.
     *
     * @param  Character  $character  The character building the plan.
     * @param  array<string, Item>  $resolvedItems  The resolved items keyed by position value.
     * @return int The authoritative total Gold cost.
     */
    private function totalCost(Character $character, array $resolvedItems): int
    {
        return array_sum(array_map(
            fn (Item $item): int => $this->craftingService->getItemCostForAutomation($character, $item),
            $resolvedItems,
        ));
    }

    /**
     * Build the final plan queue in the single authoritative Craft Set position order.
     *
     * @param  array<string, Item>  $resolvedItems  The resolved items keyed by position value.
     * @return array<int, CraftSetPlanEntry> The ordered queue entries.
     */
    private function buildQueue(array $resolvedItems): array
    {
        $queue = [];

        foreach (CraftSetPosition::orderedCases() as $position) {
            if (! isset($resolvedItems[$position->value])) {
                continue;
            }

            $queue[] = $this->buildQueueEntry($position, $resolvedItems[$position->value]);
        }

        return $queue;
    }

    /**
     * Resolve the optional hand positions into resolved items, validating the hand combination.
     *
     * @param  Character  $character  The character building the plan.
     * @param  array<string, int>  $setPositions  The requested position-to-item-id map.
     * @return array{items: array<string, Item>, blockers: array<int, string>} The resolved hand items and any blockers.
     */
    private function resolveHandPositions(Character $character, array $setPositions): array
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

            $item = $this->resolveHandItem($character, $setPositions[$position->value]);

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
     * Resolve the currently craftable item for a required plan position and requested item id.
     *
     * @param  Character  $character  The character building the plan.
     * @param  CraftSetPosition  $position  The plan position being resolved.
     * @param  int  $itemId  The requested item id for the position.
     * @return Item|null The matching craftable item, or null when unavailable.
     */
    private function resolvePositionItem(Character $character, CraftSetPosition $position, int $itemId): ?Item
    {
        $item = $this->craftingService->findCraftableItemForAutomation($character, $itemId);

        if (is_null($item) || $item->type !== $position->requiredItemType()) {
            return null;
        }

        return $item;
    }

    /**
     * Resolve the currently craftable hand item (weapon or shield) for a requested item id.
     *
     * @param  Character  $character  The character building the plan.
     * @param  int  $itemId  The requested item id for the hand position.
     * @return Item|null The matching craftable hand item, or null when unavailable.
     */
    private function resolveHandItem(Character $character, int $itemId): ?Item
    {
        $item = $this->craftingService->findCraftableItemForAutomation($character, $itemId);

        if (is_null($item) || ! $this->setHandsValidation->isHandItem($item)) {
            return null;
        }

        return $item;
    }

    /**
     * Build one deterministic queue entry for a resolved plan position and item.
     *
     * @param  CraftSetPosition  $position  The plan position.
     * @param  Item  $item  The resolved item for the position.
     * @return CraftSetPlanEntry The queue entry.
     */
    private function buildQueueEntry(CraftSetPosition $position, Item $item): CraftSetPlanEntry
    {
        return new CraftSetPlanEntry(
            $position,
            $item->id,
            $position->craftingGroup()?->value ?? $this->resolveHandCraftingType($item),
            $item->affix_name ?? $item->name,
        );
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
