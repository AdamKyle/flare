<?php

namespace App\Game\Gems\Values;

class DuplicateGemAbility
{
    /**
     * @param int $abilityId
     * @param string $abilityName
     * @param int $count
     * @param array $items
     */
    public function __construct(
        public readonly int $abilityId,
        public readonly string $abilityName,
        public readonly int $count,
        public readonly array $items,
    ) {}

    /**
     * Determine whether the duplicate group includes the given Item.
     *
     * @param int $itemId
     * @return bool
     */
    public function includesItem(int $itemId): bool
    {
        return array_any($this->items, fn (DuplicateGemAbilityItem $item): bool => $item->itemId === $itemId);
    }

    /**
     * Determine whether the duplicate group includes an Item from the given Inventory Set.
     *
     * @param int $inventorySetId
     * @return bool
     */
    public function includesInventorySet(int $inventorySetId): bool
    {
        return array_any($this->items, fn (DuplicateGemAbilityItem $item): bool => $item->setId === $inventorySetId);
    }

    /**
     * Serialize the duplicate group into the warning payload.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'ability' => [
                'id' => $this->abilityId,
                'name' => $this->abilityName,
            ],
            'count' => $this->count,
            'items' => array_map(
                fn (DuplicateGemAbilityItem $item): array => $item->toArray(),
                $this->items,
            ),
        ];
    }
}
