<?php

namespace App\Flare\MapGenerator\Contracts;

interface MapPixelReaderFactory
{
    /**
     * Build a pixel reader for the given raw image binary data.
     */
    public function fromBinary(string $imageData): MapPixelReader;
}
