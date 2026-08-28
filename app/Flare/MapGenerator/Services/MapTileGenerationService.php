<?php

namespace App\Flare\MapGenerator\Services;

use App\Flare\MapGenerator\Values\PreparedMapTileReplacement;
use App\Flare\Models\GameMap;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class MapTileGenerationService
{
    /**
     * @param  ImageTilerService  $imageTilerService  Image tile generator.
     */
    public function __construct(private readonly ImageTilerService $imageTilerService) {}

    /**
     * Generate initial Game Map tiles when committed tiles are unavailable, replacing stale partial output when necessary.
     *
     * @param  GameMap  $gameMap  Game Map requiring initial tiles.
     * @return void Tiles are generated when absent.
     */
    public function tile(GameMap $gameMap): void
    {
        $folderName = $this->piecesFolder($gameMap->name);
        $directoryExists = Storage::disk('maps')->exists($folderName);

        if ($directoryExists && ! is_null($gameMap->tile_map)) {
            return;
        }

        if ($directoryExists) {
            $this->deleteDirectory($folderName);
        }

        $tileMap = $this->generate($gameMap, $folderName, $folderName);

        GameMap::query()->whereKey($gameMap->getKey())->update([
            'tile_map' => $tileMap,
        ]);
        $gameMap->tile_map = $tileMap;
    }

    /**
     * Prepare replacement tiles without changing committed tile output or model state.
     *
     * @param  GameMap  $gameMap  Game Map carrying replacement source-image state.
     * @param  string  $previousName  Committed Game Map name before replacement.
     * @return PreparedMapTileReplacement Prepared replacement state.
     */
    public function prepareReplacement(GameMap $gameMap, string $previousName): PreparedMapTileReplacement
    {
        $previousFolderName = $this->piecesFolder($previousName);
        $currentFolderName = $this->piecesFolder($gameMap->name);
        $replacementFolderName = $currentFolderName.'-replacement';
        $backupFolderName = $currentFolderName.'-backup';

        $this->deleteDirectoryWhenPresent($replacementFolderName);
        $this->deleteDirectoryWhenPresent($backupFolderName);

        try {
            $tileMap = $this->generate($gameMap, $replacementFolderName, $currentFolderName);
        } catch (Throwable $generationFailure) {
            try {
                $this->deleteDirectoryWhenPresent($replacementFolderName);
            } catch (Throwable $cleanupFailure) {
                throw new RuntimeException(
                    'Game Map replacement tile generation failed: '.$generationFailure->getMessage()
                    .' Partial replacement cleanup also failed: '.$cleanupFailure->getMessage(),
                    previous: $generationFailure,
                );
            }

            throw $generationFailure;
        }

        return new PreparedMapTileReplacement(
            tileMap: $tileMap,
            previousFolderName: $previousFolderName,
            currentFolderName: $currentFolderName,
            replacementFolderName: $replacementFolderName,
            backupFolderName: $backupFolderName,
            hadCurrentDirectory: Storage::disk('maps')->exists($currentFolderName),
            sameCommittedDirectory: $previousFolderName === $currentFolderName,
        );
    }

    /**
     * Promote prepared replacement tiles while preserving current committed output.
     *
     * @param  PreparedMapTileReplacement  $replacement  Prepared replacement state.
     * @return void Replacement tiles become the current committed output.
     */
    public function commitReplacement(PreparedMapTileReplacement $replacement): void
    {
        if ($replacement->hadCurrentDirectory && ! Storage::disk('maps')->move(
            $replacement->currentFolderName,
            $replacement->backupFolderName,
        )) {
            throw new RuntimeException('Failed to preserve the current Game Map tile directory.');
        }

        if (Storage::disk('maps')->move($replacement->replacementFolderName, $replacement->currentFolderName)) {
            return;
        }

        $promotionFailure = new RuntimeException('Failed to commit replacement Game Map tile directory.');

        try {
            $this->restoreCurrentDirectory($replacement);
        } catch (Throwable $restoreFailure) {
            throw new RuntimeException(
                $promotionFailure->getMessage().' Restoration also failed: '.$restoreFailure->getMessage(),
                previous: $promotionFailure,
            );
        }

        throw $promotionFailure;
    }

    /**
     * Irreversibly remove obsolete tile output after the replacement model has been persisted.
     *
     * A finalization failure must not cause the caller to restore the previous database state.
     *
     * @param  PreparedMapTileReplacement  $replacement  Committed replacement state.
     * @return void Obsolete and backup directories are removed.
     */
    public function finalizeReplacement(PreparedMapTileReplacement $replacement): void
    {
        if (! $replacement->sameCommittedDirectory) {
            $this->deleteDirectoryWhenPresent($replacement->previousFolderName);
        }

        $this->deleteDirectoryWhenPresent($replacement->backupFolderName);
    }

    /**
     * Restore promoted replacement output after commit or persistence failure before finalization begins.
     *
     * This boundary must not be called for finalization or post-commit source cleanup failures.
     *
     * @param  GameMap  $gameMap  Game Map whose in-memory tile state must be restored.
     * @param  array<int, array<int, string>>|null  $previousTileMap  Previously committed tile URL map.
     * @param  PreparedMapTileReplacement  $replacement  Committed replacement state.
     * @return void Previous tile output and in-memory state are restored.
     */
    public function rollbackReplacement(
        GameMap $gameMap,
        ?array $previousTileMap,
        PreparedMapTileReplacement $replacement,
    ): void {
        $failures = [];

        try {
            $this->deleteDirectoryWhenPresent($replacement->currentFolderName);
        } catch (Throwable $failure) {
            $failures[] = $failure;
        }

        try {
            $this->restoreCurrentDirectory($replacement);
        } catch (Throwable $failure) {
            $failures[] = $failure;
        }

        $gameMap->tile_map = $previousTileMap;

        if ($failures !== []) {
            throw new RuntimeException($this->failureMessage('Failed to roll back Game Map replacement tiles.', $failures));
        }
    }

    /**
     * Remove tile output belonging to a failed Game Map creation.
     *
     * @param  GameMap  $gameMap  Failed Game Map creation.
     * @return void Failed creation tiles are removed.
     */
    public function remove(GameMap $gameMap): void
    {
        $this->deleteDirectoryWhenPresent($this->piecesFolder($gameMap->name));
    }

    /**
     * Generate and persist the tile map for the supplied pieces folder.
     *
     * @param  GameMap  $gameMap  Game Map whose source image is tiled.
     * @param  string  $folderName  Physical output folder.
     * @param  string  $publicFolderName  Committed public folder represented in tile URLs.
     * @return array<int, array<int, string>> Generated tile URL map.
     */
    private function generate(GameMap $gameMap, string $folderName, string $publicFolderName): array
    {
        $imagePath = Storage::disk('maps')->path($gameMap->path);

        return $this->imageTilerService->breakIntoTiles($imagePath, $folderName, $publicFolderName);
    }

    /**
     * Delete an existing tile directory and surface a failed required deletion.
     *
     * @param  string  $folderName  Tile directory to delete when present.
     * @return void The directory is absent after completion.
     */
    private function deleteDirectoryWhenPresent(string $folderName): void
    {
        if (! Storage::disk('maps')->exists($folderName)) {
            return;
        }

        $this->deleteDirectory($folderName);
    }

    /**
     * Delete a required existing tile directory and surface a failed deletion.
     *
     * @param  string  $folderName  Existing tile directory to delete.
     * @return void The directory is absent after completion.
     *
     * @throws RuntimeException When the existing directory cannot be deleted.
     */
    private function deleteDirectory(string $folderName): void
    {
        if (! Storage::disk('maps')->deleteDirectory($folderName)) {
            throw new RuntimeException('Failed to delete Game Map tile directory: '.$folderName.'.');
        }
    }

    /**
     * Restore the preserved current directory after replacement promotion fails.
     *
     * @param  PreparedMapTileReplacement  $replacement  Replacement state identifying current and backup output.
     * @return void The preserved directory is restored when required.
     */
    private function restoreCurrentDirectory(PreparedMapTileReplacement $replacement): void
    {
        if (! $replacement->hadCurrentDirectory) {
            return;
        }

        if (! Storage::disk('maps')->move($replacement->backupFolderName, $replacement->currentFolderName)) {
            throw new RuntimeException('Failed to restore the current Game Map tile directory.');
        }
    }

    /**
     * Build an exception message containing every compensation failure.
     *
     * @param  string  $message  Failure context.
     * @param  array<int, Throwable>  $failures  Compensation failures to include.
     * @return string Combined failure message.
     */
    private function failureMessage(string $message, array $failures): string
    {
        foreach ($failures as $failure) {
            $message .= ' '.$failure->getMessage();
        }

        return $message;
    }

    /**
     * Resolve the conventional pieces folder for a Game Map name.
     *
     * @param  string  $gameMapName  Game Map name.
     * @return string Conventional tile directory.
     */
    private function piecesFolder(string $gameMapName): string
    {
        return Str::lower($gameMapName).'-pieces';
    }
}
