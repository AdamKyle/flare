<?php

namespace App\Game\Character\CharacterInventory\Transformers;

use App\Flare\Models\InventorySet;
use League\Fractal\TransformerAbstract;

class InventorySetOptionTransformer extends TransformerAbstract
{
    /**
     * Transform an Inventory Set into its selectable set-option payload shape.
     *
     * @param  InventorySet  $inventorySet  The Inventory Set being transformed.
     * @return array{set_id: int, name: string|null, equipped: bool, is_batch_crafting_set: bool, max_slots: int|null, current_slots: int, remaining_slots: int|null, set_number: int|null, display_name: string} The transformed set option.
     */
    public function transform(InventorySet $inventorySet): array
    {
        $currentSlots = $inventorySet->slots_count;
        $maxSlots = $inventorySet->max_slots;
        $setNumber = $inventorySet->set_number;

        return [
            'set_id' => $inventorySet->id,
            'name' => $inventorySet->name,
            'equipped' => $inventorySet->is_equipped,
            'is_batch_crafting_set' => $inventorySet->isBatchCraftingSet(),
            'max_slots' => $maxSlots,
            'current_slots' => $currentSlots,
            'remaining_slots' => is_null($maxSlots) ? null : max(0, $maxSlots - $currentSlots),
            'set_number' => $setNumber,
            'display_name' => $this->resolveDisplayName($inventorySet, $setNumber),
        ];
    }

    /**
     * Resolve the selectable set option's display name, falling back to its normal-set ordinal when unnamed.
     *
     * @param  InventorySet  $inventorySet  The Inventory Set being transformed.
     * @param  int|null  $setNumber  The set's ordinal among the character's normal Inventory Sets, when supplied.
     * @return string The resolved display name.
     */
    private function resolveDisplayName(InventorySet $inventorySet, ?int $setNumber): string
    {
        if (! is_null($inventorySet->name)) {
            return $inventorySet->name;
        }

        return 'Set '.$setNumber;
    }
}
