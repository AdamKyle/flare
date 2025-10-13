<?php

namespace App\Flare\Items\Comparison;

use App\Flare\Items\Enricher\EquippableEnricher;
use App\Flare\Items\Transformers\BaseEquippableItemTransformer;
use App\Flare\Items\Values\ArmourType;
use App\Flare\Items\Values\EquippablePositionType;
use App\Flare\Items\Values\ItemType;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\SetSlot;
use App\Flare\Traits\IsItemUnique;
use App\Flare\Transformers\Serializer\PlainDataSerializer;
use Illuminate\Database\Eloquent\Collection;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as FractalItem;

class ItemComparison
{
    use IsItemUnique;

    public function __construct(
        private readonly EquippableEnricher $enricher,
        private readonly Comparator $comparator,
        private readonly BaseEquippableItemTransformer $baseEquippableItemTransformer,
        private readonly PlainDataSerializer $plainDataSerializer,
        private readonly Manager $manager,
    ) {}

    /**
     * Build ordered comparison rows for each equipped slot that can hold the given item.
     *
     * Flow:
     * 1) Resolve valid positions for the item type.
     * 2) Filter inventory slots to those positions and equipped=true.
     * 3) Sort by resolved order (e.g., ring-one before ring-two).
     * 4) Dedupe by position.
     * 5) Enrich the candidate item, compare against each equipped slot item, and return rows.
     *
     * @param  Item  $itemToCompare  The item the player is considering equipping; enriched prior to compare.
     * @param  Collection<int, object>  $inventorySlots  Slot models/records with at least: ->position(string), ->equipped(bool), ->item(Item)
     * @param  Character  $character  Unused placeholder for future needs/signature parity.
     * @return array<int, array<string, mixed>> A list of comparison row payloads ready for the frontend.
     */
    public function fetchDetails(Item $itemToCompare, Collection $inventorySlots, Character $character): array
    {
        $equipPositions = $this->resolveEquipPositions($itemToCompare);
        if (empty($equipPositions)) {
            return [];
        }

        $matching = $this->filterSlotsByPositions($inventorySlots, $equipPositions);

        if ($matching->isEmpty()) {
            return [];
        }

        $rank = array_flip($equipPositions);

        $matching = $matching
            ->sortBy(fn ($slot) => $rank[$slot->position] ?? PHP_INT_MAX)
            ->unique('position')
            ->values();

        $enrichedItem = $this->enricher->enrich($itemToCompare->fresh());

        $slot = $character->inventory->slots->firstWhere('item_id', $enrichedItem->id);
        $slotIdOfEnrichedItem = is_null($slot) ? 0 : $slot->id;

        return $matching
            ->map(fn ($slot) => $this->buildComparisonRow($enrichedItem, $slot, $slotIdOfEnrichedItem))
            ->values()
            ->all();
    }

    /**
     * Determine the ordered list of inventory positions the given item can occupy.
     *
     * Spells → ['spell-one','spell-two']
     * Rings  → ['ring-one','ring-two']
     * Weapons (non-spell, non-ring) → ['left-hand','right-hand']
     * Armour → Positions provided by ArmourType::getArmourPositions()
     *
     * @param  Item  $item  The raw item model whose type determines valid positions.
     * @return array<int, string> Ordered positions used for filtering and sorting.
     */
    private function resolveEquipPositions(Item $item): array
    {
        $typeEnum = ItemType::tryFrom((string) $item->type);

        if ($typeEnum === ItemType::SPELL_DAMAGE || $typeEnum === ItemType::SPELL_HEALING) {
            return EquippablePositionType::values(EquippablePositionType::orderForType($typeEnum));
        }

        if ($typeEnum === ItemType::RING) {
            return EquippablePositionType::values(EquippablePositionType::orderForType($typeEnum));
        }

        if ($typeEnum !== null && in_array($typeEnum->value, ItemType::validWeapons(), true)) {
            return EquippablePositionType::values([EquippablePositionType::LEFT_HAND, EquippablePositionType::RIGHT_HAND]);
        }

        $armourPositionsMap = ArmourType::getArmourPositions();
        $armourPositions = $armourPositionsMap[$item->type] ?? null;

        if ($armourPositions !== null) {
            $list = is_array($armourPositions) ? $armourPositions : [$armourPositions];

            return array_values(array_map(fn ($p) => (string) $p, $list));
        }

        return [];
    }

    /**
     * Filter the provided inventory slots to those which:
     * - Have a position included in the allowed positions list, and
     * - Are currently equipped.
     *
     * @param  Collection<int, object>  $inventorySlots  Slot records with ->position and ->equipped.
     * @param  array<int, string>  $equipPositions  Allowed slot position identifiers.
     * @return Collection<int, object> Filtered, still-indexed slot collection.
     */
    private function filterSlotsByPositions(Collection $inventorySlots, array $equipPositions): Collection
    {
        return $inventorySlots
            ->filter(fn ($slot) => in_array($slot->position, $equipPositions, true))
            ->filter(fn ($slot) => (bool) $slot->equipped === true);
    }

    /**
     * Build a single comparison row for a matched equipped slot.
     *
     * @param  Item  $enrichedItemToCompare  The candidate item after enrichment.
     * @param  InventorySlot|SetSlot  $equippedSlot  Slot record containing ->position(string) and ->item(Item Eloquent model).
     * @return array<string, mixed> Comparison row payload including metadata and computed adjustments.
     */
    private function buildComparisonRow(Item $enrichedItemToCompare, InventorySlot|SetSlot $equippedSlot, int $enrichedItemToCompareSlotId): array
    {
        $equippedItem = $equippedSlot->item->fresh();
        $enrichedEquippedItem = $this->enricher->enrich($equippedItem);
        $payload = $this->comparator->compare($enrichedItemToCompare, $enrichedEquippedItem);
        $summary = $payload['comparison'];

        $itemToCompareData = new FractalItem($enrichedItemToCompare, $this->baseEquippableItemTransformer->setSlotId($enrichedItemToCompareSlotId));
        $itemToCompareData = $this->manager->setSerializer($this->plainDataSerializer)->createData($itemToCompareData)->toArray();

        return [
            'position' => $equippedSlot->position,
            'equipped_item' => [
                'affix_count' => $enrichedEquippedItem->affix_count,
                'max_holy_stacks' => $enrichedEquippedItem->holy_stacks,
                'holy_stacks_applied' => $enrichedEquippedItem->holy_stacks_applied,
                'holy_stacks_total_stat_increase' => $enrichedEquippedItem->holy_stack_stat_bonus,
                'is_cosmic' => $enrichedEquippedItem->is_cosmic,
                'is_mythic' => $enrichedEquippedItem->is_mythic,
                'is_unique' => $this->isUnique($enrichedEquippedItem),
                'usable' => $enrichedEquippedItem->usable,
                'holy_level' => $enrichedEquippedItem->holy_level,
                'damages_kingdoms' => $enrichedEquippedItem->damages_kingdoms,
                'name' => $enrichedEquippedItem->affix_name,
                'description' => $enrichedEquippedItem->description,
                'type' => $enrichedEquippedItem->type,
                'slot_id' => $equippedSlot->id,
            ],
            'item_to_equip' => $itemToCompareData,
            'comparison' => [
                'adjustments' => $summary['adjustments'],
            ],
        ];
    }
}
