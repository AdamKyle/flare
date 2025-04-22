<?php

namespace App\Flare\MapGenerator\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

class ImageTilerService
{
    /**
     * @var int $tileSize
     */
    private int $tileSize = 250;

    /**
     * @param ImageManager $imageManager
     */
    public function __construct(private readonly ImageManager $imageManager) {}

    /**
     * Break image into pieces.
     *
     * @param string $imagePath
     * @param string $folderName
     * @return array
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
     *
     * @param ImageInterface $image
     * @param int $width
     * @param int $height
     * @param string $folder
     * @return array
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
     *
     * @param ImageInterface $image
     * @param int $x
     * @param int $y
     * @param string $folder
     * @return string
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
