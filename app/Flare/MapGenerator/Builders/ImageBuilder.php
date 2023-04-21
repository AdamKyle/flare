<?php

namespace App\Flare\MapGenerator\Builders;

use Storage;

class ImageBuilder {

    /**
     * Turn the image into a file and store the image in a specified location.
     *
     * @param mixed $image
     * @param string $storageDisk | public
     * @param string $fileName
     * @return void
     */
    public static function buildAndStoreImage($image, string $fileName, string $storageDisk = 'public', ): void {
        ob_start();

        imagepng($image);
        $imageData = ob_get_contents();

        Storage::disk($storageDisk)->put($fileName . '.jpeg', $imageData);
        imagedestroy($image);

        ob_clean();
    }
}
