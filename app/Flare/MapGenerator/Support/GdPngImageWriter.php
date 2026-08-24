<?php

namespace App\Flare\MapGenerator\Support;

use Illuminate\Support\Facades\Storage;

class GdPngImageWriter
{
    /**
     * Encode the given GD image resource as a PNG and store it on the given disk.
     */
    public function encodeAndStore(mixed $gdImage, string $disk, string $path): void
    {
        ob_start();
        imagepng($gdImage);
        $imageData = ob_get_contents();
        ob_end_clean();

        Storage::disk($disk)->put($path, $imageData);

        imagedestroy($gdImage);
    }
}
