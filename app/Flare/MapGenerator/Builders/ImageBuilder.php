<?php

namespace App\Flare\MapGenerator\Builders;

use Storage;

class ImageBuilder {

    public static function buildAndStoreImage($image, string $storageDisk = 'public', string $fileName): void {
        ob_start();

        imagepng($image);
        $imageData = ob_get_contents();

        Storage::disk($storageDisk)->put($fileName . '.jpeg', $imageData);
        imagedestroy($image);

        ob_clean();
    }
}
