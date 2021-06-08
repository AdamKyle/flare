<?php

namespace App\Game\Maps\Values;

use App\Flare\Models\Character;
use Illuminate\Support\Facades\Storage;

class MapTileValue {

    /**
     * Get the tile color from the current map.
     *
     * @param Character $character
     * @param int $xPosition
     * @param int $yPosition
     * @return string
     */
    public function getTileColor(Character $character, int $xPosition, int $yPosition): string {
        $contents            = Storage::disk('maps')->get($character->map->gameMap->path);

        $this->imageResource = imagecreatefromstring($contents);

        $rgb                 = imagecolorat($this->imageResource, $xPosition, $yPosition);

        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;

        return $r.$g.$b;
    }

    /**
     * Is the current tile a water tile?
     *
     * @param int $color
     * @return bool
     */
    public function isWaterTile(int $color): bool {
        // These represents water:
        $invalidColors = [
            115217255, 114217255, 112219255, 112217247, 106222255, 117217251,
            115223255, 111219255, 112219253, 117216245, 110220255, 110222255,
            105222255, 114218255, 104223255, 118218252, 94224255, 115218251,
            114217255,
        ];

        return in_array($color, $invalidColors);
    }

    /**
     * Can we teleport to water based locations?
     *
     * @param Character $character
     * @param int $x
     * @param int $y
     * @return bool
     */
    public function canWalkOnWater(Character $character, int $x, int $y): bool {

        $color = $this->getTileColor($character, $x, $y);

        if ($this->isWaterTile((int) $color)) {
            $hasItem = $character->inventory->slots->filter(function($slot) {
                return $slot->item->effect === 'walk-on-water';
            })->isNotEmpty();

            return $hasItem;
        }

        // We are not water
        return true;
    }
}
