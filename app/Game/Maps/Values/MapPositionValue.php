<?php

namespace App\Game\Maps\Values;

class MapPositionValue {

    public function fetchXPosition(int $characterX, int $mapPositionX): int {
        if ($characterX === 464) {
            return 0;
        }
    
        if ($characterX > 464) {
            return -150;
        }
    
        return $mapPositionX;
    }

    public function fetchYPosition(int $characterY, int $mapPositionY): int {
        if ($characterY < 320) {
            return 0;
        }

        if ($characterY > 320) {
            return -150;
        }
    
        return $mapPositionY;
    }
}