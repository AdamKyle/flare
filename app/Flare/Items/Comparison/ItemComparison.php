<?php

namespace App\Flare\Items\Comparison;

use App\Flare\Items\Enricher\EquippableEnricher;
use App\Flare\Items\Values\ArmourType;
use App\Flare\Items\Values\ItemType;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Traits\IsItemUnique;
use Illuminate\Database\Eloquent\Collection;

class ItemComparison
{
    use IsItemUnique;

    /**
     * @param EquippableEnricher $enricher   Enriches raw Item models with computed fields required by the Comparator.
     * @param Comparator         $comparator Computes deltas/adjustments between two enriched items.
     */
    public function __construct(
        private readonly EquippableEnricher $enricher,
        private readonly Comparator $comparator,
    ) {}

    /**
     * Build comparison rows for each equipped slot that could hold the given item.
     *
     * Flow:
     * 1) Resolve positions the item can occupy.
     * 2) Filter inventory slots to those positions (and, if present, equipped=true).
     * 3) Sort positions descending to match UI: right-hand before left-hand, spell-two before spell-one, ring-two before ring-one.
     * 4) Dedupe by position so we only compare once per hand/slot.
     * 5) Enrich candidate item once; enrich each equipped slot item; compare.
     *
     * @param Item        $itemToCompare
     * @param Collection  $inventorySlots  Eloquent collection of slot models with ->position and ->item
     * @param Character   $character       Not used here; kept for signature parity.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchDetails(Item $itemToCompare, Collection $inventorySlots, Character $character): array
    {
        $equipPositions = $this->resolveEquipPositions($itemToCompare);
        if (empty($equipPositions)) {
            return [];
        }

        $matchingSlots = $this->filterSlotsByPositions($inventorySlots, $equipPositions);
        if ($matchingSlots->isEmpty()) {
            return [];
        }

        // Sort for expected UI order, then dedupe by position.
        $matchingSlots = $matchingSlots
            ->sortByDesc(fn ($slot) => $slot->position, SORT_NATURAL)
            ->unique('position')
            ->values();

        $enrichedItemToCompare = $this->enricher->enrich($itemToCompare->fresh());

        return $matchingSlots
            ->map(fn ($slot) => $this->buildComparisonRow($enrichedItemToCompare, $slot))
            ->values()
            ->all();
    }

    /**
     * Determine which inventory positions an item type can occupy.
     *
     * @param Item $item
     * @return array<int, string>
     */
    private function resolveEquipPositions(Item $item): array
    {
        $explicitPositions = match ($item->type) {
            'spell-damage', 'spell-healing' => ['spell-one', 'spell-two'],
            'shield'                         => ['left-hand', 'right-hand'],
            'ring'                           => ['ring-one', 'ring-two'],
            default                          => null,
        };

        if ($explicitPositions !== null) {
            return $explicitPositions;
        }

        if (in_array($item->type, ItemType::validWeapons(), true)) {
            return ['left-hand', 'right-hand'];
        }

        $armourPositionsMap = ArmourType::getArmourPositions();
        $armourPositions    = $armourPositionsMap[$item->type] ?? null;

        if ($armourPositions !== null) {
            return $armourPositions;
        }

        return [];
    }

    /**
     * Filter inventory slots to those whose position is in the allowed list,
     * and are actually equipped. Assumes `equipped` is always present on slots.
     *
     * @param Collection $inventorySlots
     * @param array<int,string>                         $equipPositions
     * @return Collection
     */
    private function filterSlotsByPositions(Collection $inventorySlots, array $equipPositions): Collection
    {
        return $inventorySlots
            ->filter(fn ($slot) => in_array($slot->position, $equipPositions, true))
            ->filter(fn ($slot) => (bool) $slot->equipped === true);
    }


    /**
     * Build a single comparison row for a slot.
     *
     * @param Item   $enrichedItemToCompare
     * @param object $equippedSlot          Must have ->position and ->item
     * @return array<string, mixed>
     */
    private function buildComparisonRow(Item $enrichedItemToCompare, $equippedSlot): array
    {
        $equippedItem         = $equippedSlot->item->fresh();
        $enrichedEquippedItem = $this->enricher->enrich($equippedItem);
        $comparisonPayload    = $this->comparator->compare($enrichedItemToCompare, $enrichedEquippedItem);
        $comparisonSummary    = $comparisonPayload['comparison'];

        return [
            'position'             => $equippedSlot->position,
            'is_unique'            => $this->isUnique($equippedSlot->item),
            'is_mythic'            => $equippedSlot->item->is_mythic,
            'is_cosmic'            => $equippedSlot->item->is_cosmic,
            'affix_count'          => $equippedSlot->item->affix_count,
            'holy_stacks_applied'  => $equippedSlot->item->holy_stacks_applied,
            'type'                 => $equippedSlot->item->type,
            'comparison'           => [
                'to_equip_name'       => $comparisonSummary['name'],
                'to_equip_description'         => $comparisonSummary['description'],
                'adjustments'         => $comparisonSummary['adjustments'],
                'equipped_affix_name' => $equippedSlot->item->affix_name,
            ],
        ];
    }
}
