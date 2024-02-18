<?php

namespace App\Game\CharacterInventory\Validations;

use App\Flare\Models\InventorySet;
use App\Flare\Values\ArmourTypes;
use App\Flare\Values\WeaponTypes;
use Illuminate\Support\Collection;

class SetHandsValidation {

    const SINGLE_HANDED_TYPES = [
        WeaponTypes::WEAPON,
        WeaponTypes::MACE,
        WeaponTypes::GUN,
        WeaponTypes::FAN,
        WeaponTypes::SCRATCH_AWL,
        ArmourTypes::SHIELD,
    ];

    const DUEL_HANDED_TYPES = [
        WeaponTypes::BOW,
        WeaponTypes::STAVE,
        WeaponTypes::HAMMER,
    ];

    const RULES = [
        'single-handed-types' => [
            'max' => 2,
            'valid_secondary' => [
                WeaponTypes::GUN => 1,
                WeaponTypes::SCRATCH_AWL => 1,
                WeaponTypes::FAN => 1,
                WeaponTypes::MACE => 1,
                ArmourTypes::SHIELD => 1,
                WeaponTypes::WEAPON => 1,
                WeaponTypes::STAVE => 0,
                WeaponTypes::HAMMER => 0,
                WeaponTypes::BOW => 0,
            ]
        ],
        'duel-handed-types' => [
            'max' => 1,
            'valid_secondary' => [
                WeaponTypes::GUN => 0,
                WeaponTypes::SCRATCH_AWL => 0,
                WeaponTypes::FAN => 0,
                WeaponTypes::MACE => 0,
                ArmourTypes::SHIELD => 0,
                WeaponTypes::WEAPON => 0,
                WeaponTypes::STAVE => 0,
                WeaponTypes::HAMMER => 0,
                WeaponTypes::BOW => 0,
            ]
        ]
    ];

    public function isInventorySetHandPositionsValid(InventorySet $inventorySet): bool {

        $slots = $inventorySet->slots;

        foreach (self::RULES as $baseType => $itemTypeRules) {

            $isValid = match ($baseType) {
                'single-handed-types' => $this->validateSingleHandedTypes($slots, $itemTypeRules),
                'duel-handed-types' => $this->validateDuelHandedTypes($slots, $itemTypeRules),
                default => false,
            };

            if (!$isValid) {
                return false;
            }

        }

        return true;
    }

    protected function validateSingleHandedTypes(Collection $slots, array $itemTypeRules): bool {
        forEach (self::SINGLE_HANDED_TYPES as $type) {
            if (!$this->isTypeValid($slots, $type, $itemTypeRules)) {
                return false;
            }
        }

        return true;
    }

    protected function validateDuelHandedTypes(Collection $slots, array $itemTypeRules): bool {
        forEach (self::DUEL_HANDED_TYPES as $type) {
            if (!$this->isTypeValid($slots, $type, $itemTypeRules)) {
                return false;
            }
        }

        return true;
    }

    protected function isTypeValid(Collection $slots, string $type,  array $itemTypeRules): bool {

        $matchingSlots = $slots->where('item.type', $type);
        $itemTypeCount = $matchingSlots->count();
        $itemIds       = $matchingSlots->pluck('item.id')->toArray();

        if ($itemTypeCount === 0) {
            return true;
        }

        if ($itemTypeCount > $itemTypeRules['max']) {
            return false;
        }

        if (!$this->isSecondaryHandValid($slots, $type, $itemTypeRules, $itemIds, $itemTypeCount)) {
            return false;
        }

        return true;
    }

    protected function isSecondaryHandValid(Collection $slots, string $primaryType, array $itemTypeRules, array $matchingItemIds, int $primaryTypeCount): bool {
        $hasSecondaryValidItem = false;

        foreach ($itemTypeRules['valid_secondary'] as $secondaryType => $maxAllowed) {

            if ($primaryType === $secondaryType) {
                $secondaryItemCount = $slots->filter(function($slot) use($matchingItemIds, $primaryType) {
                    return !in_array($slot->item_id, $matchingItemIds) && $slot->item->type === $primaryType;
                })->count();
            } else {
                $secondaryItemCount = $slots->where('item.type', $secondaryType)->count();
            }

            if ($secondaryItemCount === 0) {
                continue;
            }

            if ($itemTypeRules['max'] === $primaryTypeCount && $secondaryItemCount > 0) {
                return false;
            }

            if ($primaryTypeCount === 1 && $secondaryItemCount > $maxAllowed) {
                return false;
            }

            if ($primaryTypeCount === 1 && $secondaryItemCount > 0 && $hasSecondaryValidItem) {
                return false;
            }

            $hasSecondaryValidItem = true;
        }

        return true;
    }

}
