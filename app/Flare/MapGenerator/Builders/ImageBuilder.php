<?php

namespace App\Flare\MapGenerator\Builders;

use App\Flare\MapGenerator\Support\GdPngImageWriter;

class ImageBuilder
{
    public function __construct(private readonly GdPngImageWriter $imageWriter) {}

    /**
     * Turn the image into a file and store the image in a specified location.
     *
     * @param mixed $image
     * @param string $storageDisk | public
     */
    public function buildAndStoreImage($image, string $fileName, string $storageDisk = 'public'): void
    {
        $this->imageWriter->encodeAndStore($image, $storageDisk, $fileName.'.jpeg');
    }
}
