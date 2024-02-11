<?php

namespace App\Game\Core\Values;

use App\Flare\Models\Item;
use App\Flare\Values\ArmourTypes;

class ValidEquipPositionsValue {

    /**
     * Get positions for an item
     *
     * @param Item $item
     * @return array
     */
    public function getPositions(Item $item): array {
        if (!is_null($item->default_position)) {
            if (in_array($item->default_position, array_merge(
                ArmourTypes::armourTypes(),
            ))) {
                return [$item->default_position];
            }
        }

        $positions = [];

        switch($item->type) {
            case 'weapon':
            case 'stave':
            case 'bow':
            case 'gun':
            case 'hammer':
            case 'shield':
                $positions = ['left-hand', 'right-hand'];
                break;
            case 'spell-damage':
            case 'spell-healing':
                $positions = ['spell-one', 'spell-two'];
                break;
            case 'ring':
                $positions = ['ring-one', 'ring-two'];
                break;
            case 'trinket':
                $positions = ['trinket'];
                break;
            case 'artifact':
                $positions = ['artifact'];
                break;
            default:
                break;
        }

        return $positions;
    }
}
