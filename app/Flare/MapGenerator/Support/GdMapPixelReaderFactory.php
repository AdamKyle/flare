<?php

namespace App\Flare\MapGenerator\Support;

use App\Flare\MapGenerator\Contracts\MapPixelReader;
use App\Flare\MapGenerator\Contracts\MapPixelReaderFactory;

class GdMapPixelReaderFactory implements MapPixelReaderFactory
{
    public function fromBinary(string $imageData): MapPixelReader
    {
        return new GdMapPixelReader($imageData);
    }
}
