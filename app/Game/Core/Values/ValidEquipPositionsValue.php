<?php

namespace App\Game\Core\Values;

use App\Flare\Models\Item;

class ValidEquipPositionsValue {

    /**
     * Get positions for an item
     *
     * @param Item $item
     * @return array
     */
    public function getPositions(Item $item): array {
        if (!is_null($item->default_position)) {
            if (!in_array($item->default_position, ['stave', 'bow', 'hammer'])) {
                return [];
            }
        }

        $positions = [];

        switch($item->type) {
            case 'weapon':
            case 'stave':
            case 'bow':
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
                $positions = ['trinket-one', 'trinket-two'];
                break;
            default:
                break;
        }

        return $positions;
    }
}
