<?php

namespace App\Game\CharacterInventory\Services;

use App\Flare\Builders\Character\Traits\FetchEquipped;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\SetSlot;
use App\Flare\Values\ArmourTypes;
use App\Flare\Values\SpellTypes;
use App\Flare\Values\WeaponTypes;
use Illuminate\Support\Collection;

class EquipBestItemForSlotsTypesService {

    use FetchEquipped;

    const TYPES = [
        WeaponTypes::WEAPON,
        WeaponTypes::STAVE, // TwoHanded
        WeaponTypes::HAMMER, // TwoHanded
        WeaponTypes::BOW, // TwoHanded
        WeaponTypes::RING,
        SpellTypes::HEALING,
        SpellTypes::DAMAGE,
        ArmourTypes::SHIELD,
        ArmourTypes::BODY,
        ArmourTypes::SLEEVES,
        ArmourTypes::LEGGINGS,
        ArmourTypes::HELMET,
        ArmourTypes::GLOVES,
        ArmourTypes::FEET,
        'trinket',
        'artifact',
    ];

    const EQUIPPABLE_POSITIONS = [
        'left-hand',
        'right-hand',
        'body',
        'shield',
        'leggings',
        'feet',
        'sleeves',
        'sleeves',
        'helmet',
        'gloves',
        'ring-one',
        'ring-two',
        'spell-one',
        'spell-two',
        'trinket',
        'artifact'
    ];

    const CORE_ATTRIBUTES = [
        'damage_adjustment',
        'base_damage_adjustment',
        'base_damage_mod_adjustment',
        'ac_adjustment',
        'base_ac_adjustment',
        'healing_adjustment',
        'base_healing_adjustment',
        'str_adjustment',
        'dur_adjustment',
        'dex_adjustment',
        'chr_adjustment',
        'int_adjustment',
        'agi_adjustment',
        'focus_adjustment',
        'fight_time_out_mod_adjustment',
        'spell_evasion_adjustment',
        'res_chance_adjustment',
        'ambush_chance_adjustment',
        'ambush_resistance_adjustment',
        'counter_chance_adjustment',
        'counter_resistance_adjustment',
    ];

    private EquipItemService $equipItemService;

    public function __construct(EquipItemService $equipItemService) {
        $this->equipItemService = $equipItemService;
    }

    public function compareAndEquipBestItems(Character $character): bool {
        $inventorySlots = $character->inventory->slots->where('equipped', false)->whereNotIn('item.type', ['alchemy', 'quest']);

        if ($inventorySlots->isEmpty()) {
            return false;
        }

        $sortedSlots = $this->getsItemsToCompare($inventorySlots);

        if (empty($sortedSlots)) {
            return false;
        }

        $bestItemsToPossiblyEquip = $this->getBestItemsInSortedInventory($sortedSlots);

        if (empty($bestItemsToPossiblyEquip)) {
            return false;
        }

        $equippedItems = $this->fetchEquipped($character);

        $itemsToEquipOrReplace = $this->getItemsToReplaceBasedOnInventory($bestItemsToPossiblyEquip, $equippedItems);

        if (empty($itemsToEquipOrReplace)) {
            return false;
        }

        dump($itemsToEquipOrReplace);
    }

    protected function getsItemsToCompare(Collection $inventorySlots): array {
        $itemsToCompare = [];

        foreach (self::TYPES as $type) {
            $itemsForType = $inventorySlots->where('item.type', $type);

            if ($itemsForType->isNotEmpty()) {
                $itemsToCompare[$type] = $itemsForType;
            }
        }

        return $itemsToCompare;
    }

    protected function getBestItemsInSortedInventory(array $sortedInventory): array {
        $bestItemsToPossiblyEquip = [];

        foreach ($sortedInventory as $type => $slots) {
            $bestForType = $this->getBestItem($slots);

            if (is_null($bestForType)) {
                continue;
            }

            if (!$this->validateOnlyOneUniqueOrMythic($bestForType, $bestItemsToPossiblyEquip) ||
                !$this->validateOnlyOneTrinket($bestForType, $bestItemsToPossiblyEquip) ||
                !$this->validateOnlyOneArtifact($bestForType, $bestItemsToPossiblyEquip))
            {
                continue;
            }

            $bestItemsToPossiblyEquip[$type] = $bestForType;
        }

        return $bestItemsToPossiblyEquip;
    }

    protected function getBestItem(Collection $slots): InventorySlot | null {
        return $slots->sortByDesc(function ($slot) {
            return array_reduce(self::CORE_ATTRIBUTES, function ($carry, $attribute) use ($slot) {
                return $carry + $slot->item->{$attribute};
            }, 0);
        })->first();
    }

    protected function getItemsToReplaceBasedOnInventory(array $bestItemsToEquip, ?Collection $equipped = null): array {
        $itemsToReplaceOrEquip = [];

        if (is_null($equipped)) {
            foreach ($bestItemsToEquip as $type => $inventorySlot) {
                $itemsToReplaceOrEquip[$type] = [
                    'slot_id'    => $inventorySlot->id,
                    'position'   => '', // TODO: Fix!!!
                    'equip_type' => $inventorySlot->item->type,
                ];
            }

            return $itemsToReplaceOrEquip;
        }

        foreach ($bestItemsToEquip as $type => $inventorySlot) {

            $equippedItem = $equipped->where('item.type', $type)->first();

            if (is_null($equipped)) {
                $itemsToReplaceOrEquip[$type] = $inventorySlot;
            }

            $bestItem = $this->fetchBestItem($inventorySlot, $equippedItem);

            if (is_null($bestItem)) {
                continue;
            }

            $itemsToReplaceOrEquip[$type] = [
                'slot_id'    => $inventorySlot->id,
                'position'   => $equippedItem->position,
                'equip_type' => $inventorySlot->type,
            ];
        }

        return $itemsToReplaceOrEquip;
    }

    protected function fetchBestItem(InventorySlot $itemToEquip, InventorySlot|SetSlot $equippedItem): InventorySlot | null {
        $equippedScore = array_reduce(self::CORE_ATTRIBUTES, function ($carry, $attribute) use ($equippedItem) {
            return $carry + $equippedItem->{$attribute};
        }, 0);

        $bestItemScore = array_reduce(self::CORE_ATTRIBUTES, function ($carry, $attribute) use ($itemToEquip) {
            return $carry + $itemToEquip->{$attribute};
        }, 0);

        return ($equippedScore >= $bestItemScore) ? null : $itemToEquip;
    }

    private function validateOnlyOneUniqueOrMythic(InventorySlot $bestItem, array $existingItems): bool {

        if (!$bestItem->item->is_mythic && !$bestItem->item->is_unique) {
            return true;
        }

        foreach ($existingItems as $type => $slot) {
            if ($slot->item->is_mythic || $slot->item->is_unique) {
                return false;
            }
        }

        return true;
    }

    private function validateOnlyOneTrinket(InventorySlot $bestItem, array $existingItems): bool {

        if ($bestItem->item->type !== 'trinket') {
            return true;
        }

        foreach ($existingItems as $type => $slot) {
            if ($slot->item->type === 'trinket') {
                return false;
            }
        }

        return true;
    }

    private function validateOnlyOneArtifact(InventorySlot $bestItem, array $existingItems): bool {

        if ($bestItem->item->type !== 'artifact') {
            return true;
        }

        foreach ($existingItems as $type => $slot) {
            if ($slot->item->type === 'artifact') {
                return false;
            }
        }

        return true;
    }
}
