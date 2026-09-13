<?php

namespace App\Flare\MapGenerator\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class MapTileMapBuilder
{
    /**
     * Reconstruct the row-major `tile_map` array from the tile piece files already present in a
     * folder on the `maps` disk, without decoding or slicing any image.
     *
     * @param string $piecesFolder
     * @return array|null
     */
    public function build(string $piecesFolder): ?array
    {
        $tiles = $this->parseTiles(Storage::disk('maps')->files($piecesFolder), $piecesFolder);

        if ($tiles->isEmpty()) {
            return null;
        }

        if (! $this->isConsistentGrid($tiles)) {
            return null;
        }

        return $this->toRowMajorTileMap($tiles, $piecesFolder);
    }

    /**
     * Parse every file matching the `{piecesFolder}_tile_{x}_{y}.png` convention into its
     * coordinates, ignoring unrelated files.
     *
     * @param array $files
     * @param string $piecesFolder
     * @return Collection
     */
    private function parseTiles(array $files, string $piecesFolder): Collection
    {
        $pattern = '/^'.preg_quote($piecesFolder, '/').'_tile_(\d+)_(\d+)\.png$/';
        $tiles = collect();

        foreach ($files as $file) {
            $filename = basename($file);

            if (! preg_match($pattern, $filename, $matches)) {
                continue;
            }

            $tiles->push([
                'x' => intval($matches[1]),
                'y' => intval($matches[2]),
                'filename' => $filename,
            ]);
        }

        return $tiles;
    }

    /**
     * Determine whether the parsed tiles form a consistent grid: every row has the same number
     * of columns and no row contains a duplicate X coordinate.
     *
     * @param Collection $tiles
     * @return bool
     */
    private function isConsistentGrid(Collection $tiles): bool
    {
        $expectedColumnCount = null;

        foreach ($tiles->groupBy('y') as $rowTiles) {
            $xValues = $rowTiles->pluck('x');

            if ($xValues->unique()->count() !== $xValues->count()) {
                return false;
            }

            if (is_null($expectedColumnCount)) {
                $expectedColumnCount = $xValues->count();

                continue;
            }

            if ($xValues->count() !== $expectedColumnCount) {
                return false;
            }
        }

        return true;
    }

    /**
     * Build the row-major tile URL map, sorted Y ascending then X ascending, matching the shape
     * produced by `ImageTilerService`.
     *
     * @param Collection $tiles
     * @param string $piecesFolder
     * @return array
     */
    private function toRowMajorTileMap(Collection $tiles, string $piecesFolder): array
    {
        return $tiles->groupBy('y')
            ->sortKeys()
            ->map(fn (Collection $rowTiles): array => $rowTiles->sortBy('x')
                ->map(fn (array $tile): string => Storage::disk('maps')->url("{$piecesFolder}/{$tile['filename']}"))
                ->values()
                ->all())
            ->values()
            ->all();
    }
}
