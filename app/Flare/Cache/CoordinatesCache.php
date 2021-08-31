<?php

namespace App\Flare\Cache;

use Cache;

class CoordinatesCache {

    /**
     * Fetches the coordinates from cache or builds them into cache.
     *
     * @return array
     */
    public function getFromCache(): array {
        $coordinates = Cache::get('coordinates');

        if (is_null($coordinates)) {
            return Cache::rememberForever('coordinates', function() {
                return [
                    'x' => $this->buildXCoordinates(),
                    'y' => $this->buildYCoordinates(),
                ];
            });
        }

        return $coordinates;
    }

    /**
     * Builds the x coordinates
     *
     * @return array
     */
    public function buildXCoordinates(): array {

        $start = 0;
        $max   = 512;
        $coordinates = [$start];

        $current = $start;

        for ($i = 2; $i <= ($max / 16); $i++) {
            $current += 16;

            array_push($coordinates, $current);
        };

        return $coordinates;
    }

    /**
     * Builds the y coordinates
     *
     * @return array
     */
    public function buildYCoordinates(): array {

        $start = 16;
        $max   = 496;
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
