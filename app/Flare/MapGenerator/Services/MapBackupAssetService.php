<?php

namespace App\Flare\MapGenerator\Services;

use App\Flare\MapGenerator\Values\GameMapPiecesFolderName;
use App\Flare\MapGenerator\Values\MapBackupAssetResult;
use App\Flare\MapGenerator\Values\MapBackupAssetStatus;
use App\Flare\Models\GameMap;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class MapBackupAssetService
{
    /**
     * @param MapTileMapBuilder $mapTileMapBuilder
     * @param string $backupRoot
     */
    public function __construct(
        private readonly MapTileMapBuilder $mapTileMapBuilder,
        private readonly string $backupRoot,
    ) {}

    /**
     * Restore/validate a Game Map's committed image and tile pieces from the committed backup
     * tree without generating or slicing any image.
     *
     * @param GameMap $gameMap
     * @return MapBackupAssetResult
     */
    public function restore(GameMap $gameMap): MapBackupAssetResult
    {
        if ($gameMap->isGeneratedGemMap()) {
            $imageResult = $this->restoreGeneratedImage($gameMap);

            if (! is_null($imageResult)) {
                return $imageResult;
            }
        }

        return $this->restorePieces($gameMap);
    }

    /**
     * Copy the committed Gem World image into the live `maps` disk when it is missing.
     *
     * Returns null when the live image already exists or was restored successfully, signalling
     * the caller to continue on to pieces restoration.
     *
     * @param GameMap $gameMap
     * @return MapBackupAssetResult|null
     */
    private function restoreGeneratedImage(GameMap $gameMap): ?MapBackupAssetResult
    {
        $liveDisk = Storage::disk('maps');

        if ($liveDisk->exists($gameMap->path)) {
            return null;
        }

        $backupDisk = $this->backupDisk();

        if (! $backupDisk->exists($gameMap->path)) {
            return new MapBackupAssetResult(
                MapBackupAssetStatus::MISSING_BACKUP,
                'Missing committed Gem World image backup for '.$gameMap->name.' at: resources/backup/maps/'.$gameMap->path,
            );
        }

        if (! $liveDisk->put($gameMap->path, $backupDisk->get($gameMap->path))) {
            return new MapBackupAssetResult(
                MapBackupAssetStatus::FAILED,
                'Failed to restore committed Gem World image for: '.$gameMap->name,
            );
        }

        return null;
    }

    /**
     * Restore/validate/repair the Game Map's tile pieces directory and `tile_map` column.
     *
     * @param GameMap $gameMap
     * @return MapBackupAssetResult
     */
    private function restorePieces(GameMap $gameMap): MapBackupAssetResult
    {
        $piecesFolder = GameMapPiecesFolderName::for($gameMap->name);
        $liveDisk = Storage::disk('maps');
        $livePiecesExist = $liveDisk->exists($piecesFolder);

        if ($livePiecesExist && ! is_null($gameMap->tile_map)) {
            return new MapBackupAssetResult(MapBackupAssetStatus::ALREADY_VALID, 'Live tiles already valid for: '.$gameMap->name);
        }

        if ($livePiecesExist) {
            return $this->repairFromLivePieces($gameMap, $piecesFolder);
        }

        return $this->restoreFromCommittedPieces($gameMap, $piecesFolder);
    }

    /**
     * Reconstruct and persist `tile_map` from an existing live pieces directory without deleting
     * or regenerating it.
     *
     * @param GameMap $gameMap
     * @param string $piecesFolder
     * @return MapBackupAssetResult
     */
    private function repairFromLivePieces(GameMap $gameMap, string $piecesFolder): MapBackupAssetResult
    {
        $tileMap = $this->mapTileMapBuilder->build($piecesFolder);

        if (is_null($tileMap)) {
            return new MapBackupAssetResult(
                MapBackupAssetStatus::FAILED,
                'Live tile pieces for '.$gameMap->name.' could not be reconstructed into a valid tile map.',
            );
        }

        $this->persistTileMap($gameMap, $tileMap);

        return new MapBackupAssetResult(MapBackupAssetStatus::REPAIRED, 'Repaired tile map for '.$gameMap->name.' from existing live tile pieces.');
    }

    /**
     * Copy the committed backup tile pieces directory onto the live `maps` disk and reconstruct
     * and persist `tile_map` from the copied pieces.
     *
     * @param GameMap $gameMap
     * @param string $piecesFolder
     * @return MapBackupAssetResult
     */
    private function restoreFromCommittedPieces(GameMap $gameMap, string $piecesFolder): MapBackupAssetResult
    {
        $backupDisk = $this->backupDisk();

        if (! $backupDisk->exists($piecesFolder)) {
            return new MapBackupAssetResult(
                MapBackupAssetStatus::MISSING_BACKUP,
                'Missing committed tile pieces backup for '.$gameMap->name.' at: resources/backup/maps/'.$piecesFolder,
            );
        }

        if (! $this->copyPiecesDirectory($backupDisk, $piecesFolder)) {
            return new MapBackupAssetResult(MapBackupAssetStatus::FAILED, 'Failed to copy committed tile pieces for: '.$gameMap->name);
        }

        $tileMap = $this->mapTileMapBuilder->build($piecesFolder);

        if (is_null($tileMap)) {
            return new MapBackupAssetResult(
                MapBackupAssetStatus::FAILED,
                'Restored tile pieces for '.$gameMap->name.' could not be reconstructed into a valid tile map.',
            );
        }

        $this->persistTileMap($gameMap, $tileMap);

        return new MapBackupAssetResult(MapBackupAssetStatus::RESTORED, 'Restored committed tile pieces for: '.$gameMap->name);
    }

    /**
     * Copy every committed file under a pieces folder onto the live `maps` disk.
     *
     * @param Filesystem $backupDisk
     * @param string $piecesFolder
     * @return bool
     */
    private function copyPiecesDirectory(Filesystem $backupDisk, string $piecesFolder): bool
    {
        $liveDisk = Storage::disk('maps');

        foreach ($backupDisk->allFiles($piecesFolder) as $file) {
            if (! $liveDisk->put($file, $backupDisk->get($file))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Persist the reconstructed tile map to the Game Map row and its in-memory instance.
     *
     * @param GameMap $gameMap
     * @param array $tileMap
     * @return void
     */
    private function persistTileMap(GameMap $gameMap, array $tileMap): void
    {
        GameMap::query()->whereKey($gameMap->getKey())->update(['tile_map' => $tileMap]);
        $gameMap->tile_map = $tileMap;
    }

    /**
     * Resolve the committed backup filesystem rooted at the configured backup root.
     *
     * @return Filesystem
     */
    private function backupDisk(): Filesystem
    {
        return Storage::build([
            'driver' => 'local',
            'root' => $this->backupRoot,
        ]);
    }
}
