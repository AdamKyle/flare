<?php

namespace App\Flare\GemWorldGeneration\Values;

use Illuminate\Support\Str;

class GeneratedGemMapPath
{
    /**
     * Resolve the deterministic committed/live image path for a generated Gem World map name,
     * before that image necessarily exists.
     *
     * @param string $mapName
     * @return string
     */
    public static function for(string $mapName): string
    {
        return 'generated-gem-worlds/'.Str::slug($mapName).'.png';
    }
}
