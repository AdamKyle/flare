<?php

namespace App\Flare\MapGenerator\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

class ImageTilerService
{
    private int $tileSize = 250;

    public function __construct(private readonly ImageManager $imageManager) {}

    /**
     * Break image into pieces.
     */
    public function breakIntoTiles(string $imagePath, string $folderName): array
    {
        $image = $this->imageManager->read($imagePath);

        $width = $image->width();
        $height = $image->height();

        Storage::disk('maps')->makeDirectory($folderName);

        $tileMap = $this->chopImage($image, $width, $height, $folderName);

        return $tileMap;
    }

    /**
     * Chop up the image.
     */
    private function chopImage(ImageInterface $image, int $width, int $height, string $folder): array
    {
        $map = [];

        for ($y = 0; $y < $height; $y += $this->tileSize) {
            $row = [];

            for ($x = 0; $x < $width; $x += $this->tileSize) {

                $filename = $this->saveTile($image, $x, $y, $folder);
                $row[] = $filename;
            }

            $map[] = $row;
        }

        return $map;
    }

    /**
     * Save the file and return the public URL.
     */
    private function saveTile(ImageInterface $image, int $x, int $y, string $folder): string
    {
        $tile = $this->imageManager->read((string) $image->encode())->crop($this->tileSize, $this->tileSize, $x, $y);
        $filename = "{$folder}_tile_{$x}_{$y}.png";
        $path = "{$folder}/{$filename}";

        Storage::disk('maps')->put($path, (string) $tile->encode());

        return Storage::disk('maps')->url($path);
    }
}
