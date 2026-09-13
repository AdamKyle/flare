<?php

namespace App\Flare\MapGenerator\Values;

use Illuminate\Support\Str;

class GameMapPiecesFolderName
{
    /**
     * Resolve the conventional live/committed tile pieces folder name for a Game Map name.
     *
     * @param string $gameMapName
     * @return string
     */
    public static function for(string $gameMapName): string
    {
        return Str::lower($gameMapName).'-pieces';
    }
}
