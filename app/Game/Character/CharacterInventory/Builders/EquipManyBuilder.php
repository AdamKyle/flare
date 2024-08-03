<?php

namespace App\Game\Character\CharacterInventory\Builders;

use App\Flare\Models\Character;
use App\Game\Core\Traits\ResponseBuilder;
use Exception;
use Illuminate\Support\Collection;

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

    private array $singlePositionTypes = ['body', 'helmet', 'artifact', 'trinket', 'leggings', 'sleeves', 'gloves', 'feet'];

    public function buildEquipmentArray(Character $character, array $slotIds): array
    {
        $inventory = $character->inventory;

        $slots = $inventory->slots->whereIn('id', $slotIds);

        $itemsByType = $this->categorizeItems($slots);

        $itemsToEquip = $this->organizeItems($itemsByType);

        $this->validateEquippableItems($itemsToEquip);

        return $itemsToEquip;
    }

    private function categorizeItems(Collection $slots): array
    {
        foreach ($slots as $slot) {
            $item = $slot->item;
            $type = $item->type;

            if (isset($this->itemsByType[$type])) {
                $position = array_shift($this->itemsByType[$type]);
                $oppositePosition = array_pop($this->itemsByType[$type]);

                if (is_null($position)) {
                    continue;
                }

                if (! is_null($oppositePosition) && ! in_array($position, $this->singlePositionTypes)) {

                    if ($this->hasPositionAlready($this->itemsByType, $position) && ! $this->hasPositionAlready($this->itemsByType, $oppositePosition)) {
                        $position = $oppositePosition;
                    }
                }

                if (is_array($position)) {
                    $type = $position['type'];
                    $equipPosition = $position['position'];

                    if (array_key_exists($equipPosition, $this->oppositePositions)) {
                        $oppositePosition = $this->oppositePositions[$equipPosition];

                        if (! is_null($oppositePosition)) {
                            $this->itemsByType[$type][] = [
                                'type' => $type,
                                'position' => $oppositePosition,
                                'slot_id' => $slot->id,
                            ];
                        }
                    } else {
                        // add the original back.
                        $this->itemsByType[$type][] = [
                            'type' => $type,
                            'position' => $equipPosition,
                            'slot_id' => $position['slot_id'],
                        ];
                    }

                    // add the original back.
                    $this->itemsByType[$type][] = [
                        'type' => $type,
                        'position' => $equipPosition,
                        'slot_id' => $slot->id,
                    ];

                    continue;
                }

                $this->itemsByType[$type][] = [
                    'type' => $type,
                    'position' => $position,
                    'slot_id' => $slot->id,
                ];
            }
        }

        return $this->itemsByType;
    }

    private function hasPositionAlready(array $itemTypes, string $position): bool
    {

        $foundItems = [];

        foreach ($itemTypes as $key => $positions) {
            // Check if the current element is an array
            foreach ($positions as $positionToEquip) {
                if (is_array($positionToEquip)) {

                    // Check if the key "position" exists in the current element
                    if (array_key_exists('position', $positionToEquip)) {

                        // Check if the value associated with the key "position" matches $x
                        if ($positionToEquip['position'] === $position) {
                            $foundItems[$key] = $positions;
                        }
                    }

                }
            }
        }

        return ! empty($foundItems);
    }

    private function organizeItems(array $itemsByType): array
    {
        $organizedItems = [];

        foreach ($itemsByType as $type => $positions) {
            foreach ($positions as $position => $item) {
                if (is_array($item)) {
                    $organizedItems[] = $item;
                }
            }
        }

        return $organizedItems;
    }

    private function validateEquippableItems(array $itemsToEquip): void
    {
        $positionCount = [];

        foreach ($itemsToEquip as $item) {
            $position = $item['position'];

            if (empty($positionCount)) {
                $positionCount[$position] = 1;

                continue;
            }

            if (! isset($positionCount[$position])) {
                $positionCount[$position] = 1;
            } else {
                $positionCount[$position]++;
            }
        }

        foreach ($positionCount as $position => $count) {
            if ($count > 1) {
                throw new Exception('You are trying to equip too many items for the position: '.ucwords(str_replace('-', ' ', $position)));
            }
        }
    }
}
