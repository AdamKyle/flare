<?php

namespace App\Flare\MapGenerator\Schemes;

use ChristianEssl\LandmapGeneration\Color\DefaultColorScheme;
use ChristianEssl\LandmapGeneration\Color\Shader\NullShader;
use ChristianEssl\LandmapGeneration\Color\Shader\ShaderInterface;
use ChristianEssl\LandmapGeneration\Struct\Color;
use ChristianEssl\LandmapGeneration\Enum\FillType;

class MapColorSheme extends DefaultColorScheme {

    public function __construct(ShaderInterface $shader = null, Color $land = null, Color $water = null) {
        if (is_null($land)) {
            $this->colors[FillType::LAND] = new Color(2, 98, 6);
        } else {
            $this->colors[FillType::LAND] = $land;
        }

        if (is_null($water)) {
            $this->colors[FillType::WATER] = new Color(24, 94, 188);
        } else {
            $this->colors[FillType::WATER] = $water;
        }

        if (is_null($shader)) {
            $shader = new NullShader();
        }

        $this->shader = $shader;
    }
}
