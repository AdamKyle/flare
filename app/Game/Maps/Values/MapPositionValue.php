<?php

namespace App\Game\Maps\Values;

class MapPositionValue {

    /**
     * Fetch the x position for the map based on character x position.
     * 
     * @param int $characterX
     * @param int $mapPositionX
     * @return int
     */
    public function fetchXPosition(int $characterX, int $mapPositionX): int {
        if ($characterX === 464) {
            return 0;
        }
    
        if ($characterX > 464) {
            return -150;
        }
    
        return $mapPositionX;
    }

    /**
     * Fetch the Y position based on the character Y position.
     * 
     * @param int $characterY
     * @return int
     */
    public function fetchYPosition(int $characterY): int {
        if ($characterY <= 320) {
            return 0;
        }

        return -25;
    }
}