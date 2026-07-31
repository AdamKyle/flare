<?php

return [
    'map_width' => env('GEM_WORLD_MAP_WIDTH', 2500),
    'map_height' => env('GEM_WORLD_MAP_HEIGHT', 2500),
    'memory_limit' => env('GEM_WORLD_GENERATION_MEMORY_LIMIT', '3G'),
    'water_color_tolerance' => env('GEM_WORLD_WATER_COLOR_TOLERANCE', 70),
];
