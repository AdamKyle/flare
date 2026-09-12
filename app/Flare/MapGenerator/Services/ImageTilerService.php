<?php

namespace App\Flare\MapGenerator\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

class ImageTilerService
{
    private int $tileSize = 250;

    /**
     * @param ImageManager $imageManager Image decoding and manipulation manager.
     */
    public function __construct(private readonly ImageManager $imageManager) {}

    /**
     * Break the image at the given path into tiles and return the resulting tile map.
     *
     * @param string $imagePath Source image path.
     * @param string $folderName Physical tile output folder.
     * @param string $publicFolderName Public folder represented in tile URLs.
     * @return array<int, array<int, string>> Generated tile URL map.
     */
    public function breakIntoTiles(string $imagePath, string $folderName, string $publicFolderName): array
    {
        $image = $this->imageManager->decodePath($imagePath);

        $width = $image->width();
        $height = $image->height();

        Storage::disk('maps')->makeDirectory($folderName);

        $tileMap = $this->chopImage($image, $width, $height, $folderName, $publicFolderName);

        return $tileMap;
    }

    /**
     * Chop the given image into tile-sized rows and columns.
     *
     * @param ImageInterface $image Decoded source image.
     * @param int $width Source image width.
     * @param int $height Source image height.
     * @param string $folder Physical tile output folder.
     * @param string $publicFolder Public folder represented in tile URLs.
     * @return array<int, array<int, string>> Generated tile URL map.
     */
    private function chopImage(
        ImageInterface $image,
        int $width,
        int $height,
        string $folder,
        string $publicFolder,
    ): array {
        $map = [];

        for ($y = 0; $y < $height; $y += $this->tileSize) {
            $row = [];

            for ($x = 0; $x < $width; $x += $this->tileSize) {

                $filename = $this->saveTile($image, $x, $y, $folder, $publicFolder);
                $row[] = $filename;
            }

            $map[] = $row;
        }

        return $map;
    }

    /**
     * Crop a single tile from the given image and save it, returning its public URL.
     *
     * @param ImageInterface $image Decoded source image.
     * @param int $x Horizontal crop coordinate.
     * @param int $y Vertical crop coordinate.
     * @param string $folder Physical tile output folder.
     * @param string $publicFolder Public folder represented in the tile URL.
     * @return string Public tile URL.
     */
    private function saveTile(ImageInterface $image, int $x, int $y, string $folder, string $publicFolder): string
    {
        $tile = $this->imageManager->decodeBinary($image->encode()->toString())->crop($this->tileSize, $this->tileSize, $x, $y);
        $filename = "{$folder}_tile_{$x}_{$y}.png";
        $path = "{$folder}/{$filename}";

        Storage::disk('maps')->put($path, $tile->encode()->toString());

        return Storage::disk('maps')->url("{$publicFolder}/{$filename}");
    }
}
