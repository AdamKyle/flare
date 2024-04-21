<?php

namespace App\Game\Maps\Values;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Values\ItemEffectsValue;
use Illuminate\Support\Facades\Storage;

class MapTileValue {

    private $imageResource;

    /**
     * Get the tile color from the current map.
     *
     * @param GameMap $gameMap
     * @param int $xPosition
     * @param int $yPosition
     * @return string
     */
    public function getTileColor(GameMap $gameMap, int $xPosition, int $yPosition): string {
        $contents            = Storage::disk('maps')->get($gameMap->path);

        $this->imageResource = imagecreatefromstring($contents);

        $rgb                 = imagecolorat($this->imageResource, $xPosition, $yPosition);

        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;

        return $r . $g . $b;
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
            105222255, 114218255, 104223255, 118218252, 115218251, 114217255,
            114216255, 110219255, 138215221, 109218255, 112219255, 109218255,
        ];

        return in_array($color, $invalidColors);
    }

    /**
     * Is the current tile a death water tile?
     *
     * @param int $color
     * @return bool
     */
    public function isDeathWaterTile(int $color): bool {
        $invalidColors = [
            255255200,
        ];

        return in_array($color, $invalidColors);
    }

    public function isMagma(int $color): bool {
        return in_array($color, [164027]);
    }

    public function isPurgatoryWater(int $color): bool {
        return in_array($color, [255255255]);
    }

    public function isIcePlaneIce(int $color): bool {
        return in_array($color, [255255255]);
    }

    public function isTwistedMemoriesWater(int $color): bool {
        return in_array($color, [3096147]);
    }

    public function isDelusionalMemoriesWater(int $color): bool {
        return in_array($color, [112219255]);
    }

    public function canWalk(Character $character, int $x, int $y) {

        if (!$this->canWalkOnWater($character, $x, $y)) {
            return false;
        }

        if (!$this->canWalkOnDeathWater($character, $x, $y)) {
            return false;
        }

        if (!$this->canWalkOnMagma($character, $x, $y)) {
            return false;
        }

        if ($character->map->gameMap->mapType()->isTheIcePlane()) {
            if (!$this->canWalkOnIcePlaneIce($character, $x, $y)) {
                return false;
            }

            return true;
        }

        if ($character->map->gameMap->mapType()->isDelusionalMemories()) {
            if (!$this->canWalkOnDelusionalMemoriesWater($character, $x, $y)) {
                return false;
            }

            return true;
        }

        if (!$this->canWalkOnPurgatoryWater($character, $x, $y)) {
            return false;
        }

        if (!$this->canWalkOnTwistedMemoriesWater($character, $x, $y)) {
            return false;
        }

        return true;
    }

    /**
     * Can the character walk on death water?
     *
     * @param Character $character
     * @param int $x
     * @param int $y
     * @return bool
     */
    public function canWalkOnDeathWater(Character $character, int $x, int $y): bool {
        $color = $this->getTileColor($character->map->gameMap, $x, $y);

        if ($this->isDeathWaterTile((int) $color)) {
            return $character->inventory->slots->filter(function ($slot) {
                return $slot->item->effect === ItemEffectsValue::WALK_ON_DEATH_WATER;
            })->isNotEmpty();
        }

        // We are not death water
        return true;
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

        $color = $this->getTileColor($character->map->gameMap, $x, $y);

        if ($this->isWaterTile((int) $color)) {
            return $character->inventory->slots->filter(function ($slot) {
                return $slot->item->effect === ItemEffectsValue::WALK_ON_WATER;
            })->isNotEmpty();
        }

        // We are not water
        return true;
    }

    /**
     * Can we walk on water?
     *
     * @param Character $character
     * @param int $x
     * @param int $y
     * @return bool
     */
    public function canWalkOnMagma(Character $character, int $x, int $y): bool {
        $color = $this->getTileColor($character->map->gameMap, $x, $y);

        if ($this->isMagma((int) $color)) {
            return $character->inventory->slots->filter(function ($slot) {
                return $slot->item->effect === ItemEffectsValue::WALK_ON_MAGMA;
            })->isNotEmpty();
        }

        // We are not death water
        return true;
    }

    /**
     * Can we walk on purgatory water?
     *
     * No we cannot if we are on purgatory water.
     *
     * @param Character $character
     * @param int $x
     * @param int $y
     * @return bool
     */
    public function canWalkOnPurgatoryWater(Character $character, int $x, int $y): bool
    {
        $color = $this->getTileColor($character->map->gameMap, $x, $y);

        if ($this->isPurgatoryWater((int) $color)) {
            return false;
        }

        return true;
    }

    /**
     * Can we walk on ice plane ice?
     *
     *
     * @param Character $character
     * @param int $x
     * @param int $y
     * @return bool
     */
    public function canWalkOnIcePlaneIce(Character $character, int $x, int $y): bool {
        $color = $this->getTileColor($character->map->gameMap, $x, $y);

        if ($this->isIcePlaneIce((int) $color)) {
            return $character->inventory->slots->filter(function ($slot) {
                return $slot->item->effect === ItemEffectsValue::WALK_ON_ICE;
            })->isNotEmpty();
        }

        return true;
    }

    /**
     * Can walk on Delusional Memories water?
     *
     * @param Character $character
     * @param int $x
     * @param int $y
     * @return bool
     */
    public function canWalkOnDelusionalMemoriesWater(Character $character, int $x, int $y): bool {
        $color = $this->getTileColor($character->map->gameMap, $x, $y);

        if ($this->isDelusionalMemoriesWater($color)) {
            return $character->inventory->slots->filter(function ($slot) {
                return $slot->item->effect === ItemEffectsValue::WALK_ON_DELUSIONAL_MEMORIES_WATER;
            })->isNotEmpty();
        }

        return true;
    }

    /**
     * Can walk on Delusional Memories water?
     *
     * @param Character $character
     * @param int $x
     * @param int $y
     * @return bool
     */
    public function canWalkOnTwistedMemoriesWater(Character $character, int $x, int $y): bool {
        $color = $this->getTileColor($character->map->gameMap, $x, $y);

        if ($this->isTwistedMemoriesWater($color)) {
            return false;
        }

        return true;
    }
}
