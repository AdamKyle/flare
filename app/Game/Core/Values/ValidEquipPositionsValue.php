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
            return [];
        }

        $positions = [];

        switch($item->type) {
            case 'weapon':
                $positions = ['left-hand', 'right-hand'];
                break;
            case 'shield':
                $positions = ['left-hand', 'right-hand'];
                break;
            case 'spell-damage':
                $positions = ['spell-one', 'spell-two'];
                break;
            case 'spell-healing':
                $positions = ['spell-one', 'spell-two'];
                break;
            case 'ring':
                $positions = ['ring-one', 'ring-two'];
                break;
            case 'artifact':
                $positions = ['artifact-one', 'artifact-two'];
                break;
            default:
                break;
        }

        return $positions;
    }
}