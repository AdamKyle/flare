<?php

namespace App\Flare\Cache;

use Cache;

class CoordinatesCache {

    public function getFromCache(): array {
        return Cache::rememberForever('coordinates', function() {
            return [
                'x' => $this->buildXCoordinates(),
                'y' => $this->buildYCoordinates(),
            ];
        });
    }

    public function buildXCoordinates(): array {
        
        $start = 16;
        $max   = 1984;
        $coordinates = [$start];

        $current = $start;

        for ($i = 2; $i <= ($max / $start); $i++) {
            $current += 16;

            array_push($coordinates, $current);
        };

        return $coordinates;
    }

    public function buildYCoordinates(): array {
        
        $start = 32;
        $max   = 1984;
        $coordinates = [$start];

        $current = $start;

        for ($i = 2; $i <= ($max / 16); $i++) {
            $current += 16;

            if ($current <= $max) {
                array_push($coordinates, $current);
            }
        };

        return $coordinates;
    }
}