<?php

namespace App\Flare\GemWorldGeneration\Values;

class GemWorldGenerationConfig
{
    public function __construct(
        public readonly string $memoryLimit,
        public readonly int $mapWidth,
        public readonly int $mapHeight,
        public readonly int $waterColorTolerance,
    ) {}

    /**
     * Read and validate the `gem_world_generation` config once, at the
     * owning boundary, so callers never need to re-inspect `config()`.
     */
    public static function fromConfig(): self
    {
        return new self(
            memoryLimit: self::stringConfig('gem_world_generation.memory_limit', '3G'),
            mapWidth: self::intConfig('gem_world_generation.map_width', 2500),
            mapHeight: self::intConfig('gem_world_generation.map_height', 2500),
            waterColorTolerance: self::intConfig('gem_world_generation.water_color_tolerance', 70),
        );
    }

    private static function intConfig(string $key, int $default): int
    {
        $value = filter_var(config($key, $default), FILTER_VALIDATE_INT);

        return $value === false ? $default : $value;
    }

    private static function stringConfig(string $key, string $default): string
    {
        $value = config($key, $default);

        return is_string($value) ? $value : $default;
    }
}
