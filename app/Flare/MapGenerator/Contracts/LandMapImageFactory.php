<?php

namespace App\Flare\MapGenerator\Contracts;

use ChristianEssl\LandmapGeneration\Settings\MapSettings;

interface LandMapImageFactory
{
    /**
     * Generate a land map from the given settings and seed, returning a raw GD image resource.
     */
    public function build(MapSettings $settings, string $seed): mixed;
}
