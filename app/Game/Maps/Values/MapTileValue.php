<?php

namespace App\Game\Maps\Values;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Values\ItemEffectsValue;
use Illuminate\Support\Facades\Storage;

class MapTileValue
{
    private $imageResource;

    private ?string $loadedMapPath = null;

    private array $itemEffects = [];

    private GameMap $gameMap;

    const WATER_TILES = [
        74146170, // Surface, Labyrinth
        255255200, // Dungeons
        164027, // Hell
        255255255, // Purgatory
        255255255, // Ice Plane
        3096147, // Twisted Memories
        112219255, // Delusional Memories
    ];

    public function setUp(Character $character, GameMap $gameMap): MapTileValue {
        $this->itemEffects = $character->inventory->slots()
            ->join('items', 'inventory_slots.item_id', '=', 'items.id')
            ->whereNotNull('items.effect')
            ->distinct()
            ->pluck('items.effect')
            ->toArray();

        $this->gameMap = $gameMap;

        return $this;
    }

    /**
     * Get the tile color from the current map.
     */
    public function getTileColor(int $xPosition, int $yPosition): string
    {
        $path = $this->gameMap->path;

        if ($this->loadedMapPath !== $path || is_null($this->imageResource)) {
            if (! is_null($this->imageResource)) {
                imagedestroy($this->imageResource);
            }

            $contents = Storage::disk('maps')->get($path);

            $this->imageResource = imagecreatefromstring($contents);
            $this->loadedMapPath = $path;
        }

        $rgbIndex = imagecolorat($this->imageResource, $xPosition, $yPosition);

        $rgbArray = imagecolorsforindex($this->imageResource, $rgbIndex);

        return $rgbArray['red'].$rgbArray['green'].$rgbArray['blue'];
    }

    /**
     * Is the current tile a water tile?
     */
    public function isWaterTile(int $color): bool
    {
        return in_array($color, self::WATER_TILES);
    }

    /**
     * Is the current tile a death water tile?
     */
    public function isDeathWaterTile(int $color): bool
    {
        return in_array($color, self::WATER_TILES);
    }

    public function isMagma(int $color): bool
    {
        return in_array($color, self::WATER_TILES);
    }

    public function isPurgatoryWater(int $color): bool
    {
        return in_array($color, self::WATER_TILES);
    }

    public function isIcePlaneIce(int $color): bool
    {
        return in_array($color, self::WATER_TILES);
    }

    public function isTwistedMemoriesWater(int $color): bool
    {
        return in_array($color, self::WATER_TILES);
    }

    public function isDelusionalMemoriesWater(int $color): bool
    {
        return in_array($color, self::WATER_TILES);
    }

    public function canWalk(int $x, int $y)
    {
        $mapType = $this->gameMap->mapType();

        if (($mapType->isSurface() || $mapType->isLabyrinth()) && !$this->canWalkOnWater($x, $y)) {
            return false;
        }

        if ($mapType->isDungeons() && !$this->canWalkOnDeathWater($x, $y)) {
            return false;
        }

        if ($mapType->isHell() && !$this->canWalkOnMagma($x, $y)) {
            return false;
        }

        if ($mapType->isTheIcePlane() && !$this->canWalkOnIcePlaneIce($x, $y)) {
            return false;
        }

        if ($mapType->isDelusionalMemories() && !$this->canWalkOnDelusionalMemoriesWater($x, $y)) {
            return false;
        }

        if ($mapType->isPurgatory() && !$this->canWalkOnPurgatoryWater($x, $y)) {
            return false;
        }

        if ($mapType->isTwistedMemories() && !$this->canWalkOnTwistedMemoriesWater($x, $y)) {
            return false;
        }

        return true;
    }

    /**
     * Can the character walk on death water?
     */
    public function canWalkOnDeathWater(int $x, int $y): bool
    {
        $color = $this->getTileColor($x, $y);

        if ($this->isDeathWaterTile((int) $color)) {
            return in_array(ItemEffectsValue::WALK_ON_DEATH_WATER, $this->itemEffects);
        }

        // We are not death water
        return true;
    }

    /**
     * Can we teleport to water based locations?
     */
    public function canWalkOnWater(int $x, int $y): bool
    {

        $color = $this->getTileColor($x, $y);

        if ($this->isWaterTile((int) $color)) {
            return in_array(ItemEffectsValue::WALK_ON_WATER, $this->itemEffects);
        }

        // We are not water
        return true;
    }

    /**
     * Can we walk on water?
     */
    public function canWalkOnMagma(int $x, int $y): bool
    {
        $color = $this->getTileColor($x, $y);

        if ($this->isMagma((int) $color)) {
            dump($this->itemEffects);
            return in_array(ItemEffectsValue::WALK_ON_MAGMA, $this->itemEffects);
        }

        // We are not death water
        return true;
    }

    /**
     * Can we walk on purgatory water?
     *
     * No we cannot if we are on purgatory water.
     */
    public function canWalkOnPurgatoryWater(int $x, int $y): bool
    {
        $color = $this->getTileColor($x, $y);

        if ($this->isPurgatoryWater((int) $color)) {
            return false;
        }

        return true;
    }

    /**
     * Can we walk on ice plane ice?
     */
    public function canWalkOnIcePlaneIce(int $x, int $y): bool
    {
        $color = $this->getTileColor($x, $y);

        if ($this->isIcePlaneIce((int) $color)) {
            return in_array(ItemEffectsValue::WALK_ON_ICE, $this->itemEffects);
        }

        return true;
    }

    /**
     * Can walk on Delusional Memories water?
     */
    public function canWalkOnDelusionalMemoriesWater(int $x, int $y): bool
    {
        $color = $this->getTileColor($x, $y);

        if ($this->isDelusionalMemoriesWater($color)) {
            return in_array(ItemEffectsValue::WALK_ON_DELUSIONAL_MEMORIES_WATER, $this->itemEffects);
        }

        return true;
    }

    /**
     * Can walk on Delusional Memories water?
     */
    public function canWalkOnTwistedMemoriesWater(int $x, int $y): bool
    {
        $color = $this->getTileColor($x, $y);

        if ($this->isTwistedMemoriesWater($color)) {
            return false;
        }

        return true;
    }
}
