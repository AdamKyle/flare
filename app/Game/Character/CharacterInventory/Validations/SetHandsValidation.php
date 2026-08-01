<?php

namespace App\Game\Character\CharacterInventory\Validations;

use App\Flare\Items\Values\ItemType;
use App\Flare\Models\InventorySet;
use App\Flare\Models\Item;
use App\Flare\Values\WeaponTypes;
use Illuminate\Support\Collection;

class SetHandsValidation
{
    public const DUEL_HANDED_TYPES = [
        ItemType::BOW->value,
        ItemType::STAVE->value,
        ItemType::HAMMER->value,
    ];

    public function isInventorySetHandPositionsValid(InventorySet $inventorySet): bool
    {
        return $this->areHandItemsValid(
            $inventorySet->slots
                ->map(fn ($slot) => $slot->item)
                ->filter(fn (?Item $item) => ! is_null($item) && $this->isHandItem($item))
                ->values()
        );
    }

    public function areHandItemsValid(Collection $items): bool
    {
        if ($items->count() > 2) {
            return false;
        }

        if ($items->contains(fn (Item $item) => ! $this->isHandItem($item))) {
            return false;
        }

        $twoHandedCount = $items->filter(fn (Item $item) => $this->handedness($item) === 'two_handed')->count();

        return $twoHandedCount === 0 || ($twoHandedCount === 1 && $items->count() === 1);
    }

    public function isHandItem(Item $item): bool
    {
        return $item->type === 'shield' || $item->type === WeaponTypes::WEAPON || in_array($item->type, ItemType::validWeapons(), true);
    }

    public function handedness(Item $item): ?string
    {
        if ($item->type === 'shield') {
            return 'shield';
        }

        if ($item->type === WeaponTypes::WEAPON) {
            return 'single_handed';
        }

        if (! in_array($item->type, ItemType::validWeapons(), true)) {
            return null;
        }

        return in_array($item->type, self::DUEL_HANDED_TYPES, true) ? 'two_handed' : 'single_handed';
    }
}
