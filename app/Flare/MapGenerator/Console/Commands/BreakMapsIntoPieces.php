<?php

namespace App\Flare\MapGenerator\Console\Commands;

use App\Flare\MapGenerator\Services\MapBackupAssetService;
use App\Flare\MapGenerator\Services\MapTileGenerationService;
use App\Flare\MapGenerator\Values\MapBackupAssetResult;
use App\Flare\MapGenerator\Values\MapBackupAssetStatus;
use App\Flare\Models\GameMap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BreakMapsIntoPieces extends Command
{
    protected $signature = 'break:maps-into-pieces {--generate-missing : Slice source images into tiles for maps missing both live and committed backup pieces}';

    protected $description = 'Restores/repairs Game Map tile pieces from committed backups, only slicing source images when --generate-missing is passed';

    private int $alreadyValid = 0;

    private int $restored = 0;

    private int $repaired = 0;

    private int $generated = 0;

    private int $missingBackup = 0;

    private int $failed = 0;

    /**
     * Restore/repair tile pieces for every persisted Game Map, generating missing pieces only
     * when explicitly requested.
     *
     * @param MapBackupAssetService $mapBackupAssetService
     * @param MapTileGenerationService $mapTileGenerationService
     * @return int
     */
    public function handle(
        MapBackupAssetService $mapBackupAssetService,
        MapTileGenerationService $mapTileGenerationService,
    ): int {
        $generateMissing = $this->option('generate-missing');

        foreach (GameMap::all() as $gameMap) {
            $this->processGameMap($gameMap, $mapBackupAssetService, $mapTileGenerationService, $generateMissing);
        }

        $this->showSummary();

        if ($this->missingBackup > 0 || $this->failed > 0) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Restore/repair one Game Map's tile pieces, generating them when explicitly allowed and the
     * committed backup is unavailable.
     *
     * @param GameMap $gameMap
     * @param MapBackupAssetService $mapBackupAssetService
     * @param MapTileGenerationService $mapTileGenerationService
     * @param bool $generateMissing
     * @return void
     */
    private function processGameMap(
        GameMap $gameMap,
        MapBackupAssetService $mapBackupAssetService,
        MapTileGenerationService $mapTileGenerationService,
        bool $generateMissing,
    ): void {
        $result = $mapBackupAssetService->restore($gameMap);

        if ($result->isMissingBackup() && $generateMissing) {
            $this->generateMissingTiles($gameMap, $mapTileGenerationService);

            return;
        }

        $this->recordResult($gameMap, $result);
    }

    /**
     * Slice the source image into tiles for a Game Map with no live or committed backup pieces.
     *
     * @param GameMap $gameMap
     * @param MapTileGenerationService $mapTileGenerationService
     * @return void
     */
    private function generateMissingTiles(GameMap $gameMap, MapTileGenerationService $mapTileGenerationService): void
    {
        if (! Storage::disk('maps')->exists($gameMap->path)) {
            $this->missingBackup++;
            $this->warn('Missing committed backup pieces and source image for: '.$gameMap->name);

            return;
        }

        $mapTileGenerationService->tile($gameMap, generateWhenMissing: true);
        $this->generated++;
        $this->info('Generated tile pieces for: '.$gameMap->name);
    }

    /**
     * Record and report the restoration outcome for one Game Map.
     *
     * @param GameMap $gameMap
     * @param MapBackupAssetResult $result
     * @return void
     */
    private function recordResult(GameMap $gameMap, MapBackupAssetResult $result): void
    {
        match ($result->status) {
            MapBackupAssetStatus::ALREADY_VALID => $this->alreadyValid++,
            MapBackupAssetStatus::RESTORED => $this->restored++,
            MapBackupAssetStatus::REPAIRED => $this->repaired++,
            MapBackupAssetStatus::MISSING_BACKUP => $this->missingBackup++,
            MapBackupAssetStatus::FAILED => $this->failed++,
        };

        $this->reportResult($gameMap, $result);
    }

    /**
     * Print the factual outcome message for one Game Map at the appropriate severity.
     *
     * @param GameMap $gameMap
     * @param MapBackupAssetResult $result
     * @return void
     */
    private function reportResult(GameMap $gameMap, MapBackupAssetResult $result): void
    {
        if ($result->status === MapBackupAssetStatus::MISSING_BACKUP) {
            $this->warn($result->message);

            return;
        }

        if ($result->status === MapBackupAssetStatus::FAILED) {
            $this->error($result->message);

            return;
        }

        $this->line($gameMap->name.': '.$result->message);
    }

    /**
     * Print the factual restoration counts for the completed run.
     *
     * @return void
     */
    private function showSummary(): void
    {
        $this->info('Already valid: '.$this->alreadyValid);
        $this->info('Restored: '.$this->restored);
        $this->info('Repaired: '.$this->repaired);
        $this->info('Generated: '.$this->generated);
        $this->info('Missing backup: '.$this->missingBackup);
        $this->info('Failed: '.$this->failed);
    }
}
