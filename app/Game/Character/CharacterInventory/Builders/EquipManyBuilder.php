<?php

namespace App\Game\Character\CharacterInventory\Builders;

use App\Flare\Models\Character;
use App\Game\Core\Traits\ResponseBuilder;
use Illuminate\Database\Eloquent\Collection;

class EquipManyBuilder
{
    use ResponseBuilder;

    private array $itemsByType = [
        'weapon' => ['left-hand', 'right-hand'],
        'mace' => ['left-hand', 'right-hand'],
        'scratch-awl' => ['left-hand', 'right-hand'],
        'gun' => ['left-hand', 'right-hand'],
        'fan' => ['left-hand', 'right-hand'],
        'stave' => ['left-hand', 'right-hand'],
        'hammer' => ['left-hand', 'right-hand'],
        'bow' => ['left-hand', 'right-hand'],
        'shield' => ['left-hand', 'right-hand'],
        'spell-damage' => ['spell-one', 'spell-two'],
        'spell-healing' => ['spell-one', 'spell-two'],
        'ring' => ['ring-one', 'ring-two'],
        'body' => ['body'],
        'helmet' => ['helmet'],
        'artifact' => ['artifact'],
        'trinket' => ['trinket'],
        'leggings' => ['leggings'],
        'sleeves' => ['sleeves'],
        'gloves' => ['gloves'],
        'feet' => ['feet'],
    ];

    private array $oppositePositions = [
        'left-hand' => 'right-hand',
        'spell-one' => 'spell-two',
        'ring-one' => 'ring-two',
        'right-hand' => 'left-hand',
        'spell-two' => 'spell-one',
        'ring-two' => 'ring-one',
    ];

    private array $duelHandItems = [
        'stave',
        'hammer',
        'bow',
    ];

    private array $singleHandedItems = [
        'weapon',
        'mace',
        'gun',
        'fan',
        'scratch-awl',
    ];

    public function buildEquipmentArray(Character $character, array $slotIds): array
    {
        $inventorySlots = $character->inventory->slots->whereIn('id', $slotIds);

        return $this->buildEquippableItemArray($inventorySlots);
    }

    private function buildEquippableItemArray(Collection $inventorySlots): array
    {
        $equippableItems = [];

        foreach ($inventorySlots as $slot) {
            $item = $slot->item;
            $type = $item->type;

            if (! isset($this->itemsByType[$type])) {
                continue;
            }

            $position = $this->findAvailablePosition($equippableItems, $type);

            if (! $position) {
                continue;
            }

            if (count($this->itemsByType[$type]) === 1 && $this->hasPosition($equippableItems, $this->itemsByType[$type][0])) {
                continue;
            }

            $equippableItems[] = [
                'equip_type' => $type,
                'position' => $position,
                'slot_id' => $slot->id,
            ];
        }

        return $this->removeDuplicatePositions($equippableItems);
    }

    private function findAvailablePosition(array &$equippableItems, string $type): ?string
    {
        foreach ($this->itemsByType[$type] as $position) {
            if (! $this->hasPosition($equippableItems, $position)) {
                return $position;
            }
        }

        return null;
    }

    private function hasPosition(array $equippableItems, string $position): bool
    {
        foreach ($equippableItems as $item) {
            if ($item['position'] === $position) {
                return true;
            }
        }

        return false;
    }

    private function removeDuplicatePositions(array $equippableItems): array
    {
        $uniqueItems = [];
        $positions = [];

        foreach ($equippableItems as $item) {
            if (! in_array($item['position'], $positions)) {
                $positions[] = $item['position'];
                $uniqueItems[] = $item;
            }
        }

        return $uniqueItems;
    }
}
