<?php

namespace App\Flare\MapGenerator\Support;

use App\Flare\MapGenerator\Contracts\MapPixelReader;
use RuntimeException;

class GdMapPixelReader implements MapPixelReader
{
    private mixed $resource;

    public function __construct(string $imageData)
    {
        $resource = @imagecreatefromstring($imageData);

        if ($resource === false) {
            throw new RuntimeException('Could not load generated gem world map image.');
        }

        $this->resource = $resource;
    }

    public function width(): int
    {
        return imagesx($this->resource);
    }

    public function height(): int
    {
        return imagesy($this->resource);
    }

    public function colorAt(int $x, int $y): array
    {
        return imagecolorsforindex($this->resource, imagecolorat($this->resource, $x, $y));
    }

    public function __destruct()
    {
        imagedestroy($this->resource);
    }
}
