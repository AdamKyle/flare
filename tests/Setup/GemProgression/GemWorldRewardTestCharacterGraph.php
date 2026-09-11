<?php

namespace Tests\Setup\GemProgression;

use App\Flare\Models\Character;
use App\Flare\Models\GameLocationGemParamter;
use App\Flare\Models\GameMapGemParamter;

/**
 * Small typed fixture result pairing a generated Gem World Character with
 * its owning Map or Location Gem profile.
 */
class GemWorldRewardTestCharacterGraph
{
    public function __construct(
        public readonly Character $character,
        public readonly ?GameMapGemParamter $mapProfile,
        public readonly ?GameLocationGemParamter $locationProfile,
    ) {}
}
