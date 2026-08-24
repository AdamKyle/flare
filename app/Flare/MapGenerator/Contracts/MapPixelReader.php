<?php

namespace App\Flare\MapGenerator\Contracts;

interface MapPixelReader
{
    /**
     * The width, in pixels, of the loaded map image.
     */
    public function width(): int;

    /**
     * The height, in pixels, of the loaded map image.
     */
    public function height(): int;

    /**
     * The RGB color of the pixel at the given coordinates.
     *
     * @return array{red: int, green: int, blue: int}
     */
    public function colorAt(int $x, int $y): array;
}
