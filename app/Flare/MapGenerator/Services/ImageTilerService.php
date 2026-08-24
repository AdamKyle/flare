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
     * Break the image at the given path into tiles and return the resulting tile map.
     */
    public function breakIntoTiles(string $imagePath, string $folderName): array
    {
        $image = $this->imageManager->decodePath($imagePath);

        $width = $image->width();
        $height = $image->height();

        Storage::disk('maps')->makeDirectory($folderName);

        $tileMap = $this->chopImage($image, $width, $height, $folderName);

        return $tileMap;
    }

    /**
     * Chop the given image into tile-sized rows and columns.
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
     * Crop a single tile from the given image and save it, returning its public URL.
     */
    private function saveTile(ImageInterface $image, int $x, int $y, string $folder): string
    {
        $tile = $this->imageManager->decodeBinary($image->encode()->toString())->crop($this->tileSize, $this->tileSize, $x, $y);
        $filename = "{$folder}_tile_{$x}_{$y}.png";
        $path = "{$folder}/{$filename}";

        Storage::disk('maps')->put($path, $tile->encode()->toString());

        return Storage::disk('maps')->url($path);
    }
}
