<?php

namespace Tests\Unit\Flare\MapGenerator\Services;

use App\Flare\MapGenerator\Services\ImageTilerService;
use App\Flare\MapGenerator\Services\MapBackupAssetService;
use App\Flare\MapGenerator\Services\MapTileGenerationService;
use App\Flare\MapGenerator\Values\MapBackupAssetResult;
use App\Flare\MapGenerator\Values\MapBackupAssetStatus;
use App\Flare\MapGenerator\Values\PreparedMapTileReplacement;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;

class MapTileGenerationServiceTest extends TestCase
{
    use CreateGameMap, RefreshDatabase;

    public function test_tile_returns_without_slicing_when_restore_reports_success(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Test Map']);
        $imageTilerService = Mockery::mock(ImageTilerService::class);
        $imageTilerService->shouldNotReceive('breakIntoTiles');
        $mapBackupAssetService = Mockery::mock(MapBackupAssetService::class);
        $mapBackupAssetService->shouldReceive('restore')->once()->with($gameMap)
            ->andReturn(new MapBackupAssetResult(MapBackupAssetStatus::RESTORED, 'Restored committed tile pieces for: Test Map'));

        (new MapTileGenerationService($imageTilerService, $mapBackupAssetService))->tile($gameMap);

        $this->assertNull($gameMap->fresh()->tile_map);
    }

    public function test_tile_does_nothing_when_backup_is_missing_and_generation_is_not_allowed(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Test Map']);
        $imageTilerService = Mockery::mock(ImageTilerService::class);
        $imageTilerService->shouldNotReceive('breakIntoTiles');
        $mapBackupAssetService = Mockery::mock(MapBackupAssetService::class);
        $mapBackupAssetService->shouldReceive('restore')->once()->with($gameMap)
            ->andReturn(new MapBackupAssetResult(MapBackupAssetStatus::MISSING_BACKUP, 'Missing committed tile pieces backup for: Test Map'));

        (new MapTileGenerationService($imageTilerService, $mapBackupAssetService))->tile($gameMap);

        $this->assertNull($gameMap->fresh()->tile_map);
    }

    public function test_tile_slices_the_source_image_when_backup_is_missing_and_generation_is_explicitly_allowed(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Test Map', 'path' => 'test-map.png']);
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('path')->once()->with('test-map.png')->andReturn('/tmp/test-map.png');
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);
        $imageTilerService = Mockery::mock(ImageTilerService::class);
        $imageTilerService->shouldReceive('breakIntoTiles')->once()
            ->with('/tmp/test-map.png', 'test map-pieces', 'test map-pieces')
            ->andReturn([['tile.png']]);
        $mapBackupAssetService = Mockery::mock(MapBackupAssetService::class);
        $mapBackupAssetService->shouldReceive('restore')->once()->with($gameMap)
            ->andReturn(new MapBackupAssetResult(MapBackupAssetStatus::MISSING_BACKUP, 'Missing committed tile pieces backup for: Test Map'));

        (new MapTileGenerationService($imageTilerService, $mapBackupAssetService))->tile($gameMap, generateWhenMissing: true);

        $this->assertSame([['tile.png']], $gameMap->fresh()->tile_map);
    }

    public function test_tile_does_not_slice_when_restore_fails_even_with_generation_allowed(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Test Map']);
        $imageTilerService = Mockery::mock(ImageTilerService::class);
        $imageTilerService->shouldNotReceive('breakIntoTiles');
        $mapBackupAssetService = Mockery::mock(MapBackupAssetService::class);
        $mapBackupAssetService->shouldReceive('restore')->once()->with($gameMap)
            ->andReturn(new MapBackupAssetResult(MapBackupAssetStatus::FAILED, 'Live tile pieces for Test Map could not be reconstructed into a valid tile map.'));

        (new MapTileGenerationService($imageTilerService, $mapBackupAssetService))->tile($gameMap, generateWhenMissing: true);

        $this->assertNull($gameMap->fresh()->tile_map);
    }

    public function test_preparation_generates_replacement_output_without_changing_committed_tiles(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Renamed Map', 'path' => 'replacement.png', 'tile_map' => [['old.png']]]);
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('exists')->once()->with('renamed map-pieces-replacement')->andReturn(false);
        $disk->shouldReceive('exists')->once()->with('renamed map-pieces-backup')->andReturn(false);
        $disk->shouldReceive('path')->once()->with('replacement.png')->andReturn('/tmp/replacement.png');
        $disk->shouldReceive('exists')->once()->with('renamed map-pieces')->andReturn(false);
        $disk->shouldNotReceive('move');
        $disk->shouldNotReceive('deleteDirectory')->with('original map-pieces');
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);
        $imageTilerService = Mockery::mock(ImageTilerService::class);
        $imageTilerService->shouldReceive('breakIntoTiles')->once()
            ->with('/tmp/replacement.png', 'renamed map-pieces-replacement', 'renamed map-pieces')
            ->andReturn([['fresh.png']]);

        $replacement = (new MapTileGenerationService($imageTilerService, Mockery::mock(MapBackupAssetService::class)))->prepareReplacement($gameMap, 'Original Map');

        $this->assertSame([['fresh.png']], $replacement->tileMap);
        $this->assertSame([['old.png']], $gameMap->tile_map);
    }

    public function test_preparation_failure_removes_partial_replacement_output(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Test Map', 'path' => 'replacement.png']);
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('exists')->once()->with('test map-pieces-replacement')->andReturn(false);
        $disk->shouldReceive('exists')->once()->with('test map-pieces-backup')->andReturn(false);
        $disk->shouldReceive('path')->once()->andReturn('/tmp/replacement.png');
        $disk->shouldReceive('exists')->once()->with('test map-pieces-replacement')->andReturn(true);
        $disk->shouldReceive('deleteDirectory')->once()->with('test map-pieces-replacement')->andReturn(true);
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);
        $imageTilerService = Mockery::mock(ImageTilerService::class);
        $imageTilerService->shouldReceive('breakIntoTiles')->once()->andThrow(new RuntimeException('generation failed'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('generation failed');

        (new MapTileGenerationService($imageTilerService, Mockery::mock(MapBackupAssetService::class)))->prepareReplacement($gameMap, 'Test Map');
    }

    public function test_failed_partial_output_cleanup_exposes_generation_and_cleanup_failures(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Test Map', 'path' => 'replacement.png']);
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('exists')->once()->with('test map-pieces-replacement')->andReturn(false);
        $disk->shouldReceive('exists')->once()->with('test map-pieces-backup')->andReturn(false);
        $disk->shouldReceive('path')->once()->andReturn('/tmp/replacement.png');
        $disk->shouldReceive('exists')->once()->with('test map-pieces-replacement')->andReturn(true);
        $disk->shouldReceive('deleteDirectory')->once()->with('test map-pieces-replacement')->andReturn(false);
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);
        $imageTilerService = Mockery::mock(ImageTilerService::class);
        $imageTilerService->shouldReceive('breakIntoTiles')->once()->andThrow(new RuntimeException('generation failed'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('generation failed Partial replacement cleanup also failed: Failed to delete Game Map tile directory');

        (new MapTileGenerationService($imageTilerService, Mockery::mock(MapBackupAssetService::class)))->prepareReplacement($gameMap, 'Test Map');
    }

    public function test_commit_preserves_current_tiles_as_backup_before_promotion(): void
    {
        $replacement = new PreparedMapTileReplacement([['fresh.png']], 'test map-pieces', 'test map-pieces', 'test map-pieces-replacement', 'test map-pieces-backup', true, true);
        $moves = [];
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('move')->once()->ordered()->with('test map-pieces', 'test map-pieces-backup')
            ->andReturnUsing(function (string $source, string $destination) use (&$moves): bool {
                $moves[] = [$source, $destination];

                return true;
            });
        $disk->shouldReceive('move')->once()->ordered()->with('test map-pieces-replacement', 'test map-pieces')
            ->andReturnUsing(function (string $source, string $destination) use (&$moves): bool {
                $moves[] = [$source, $destination];

                return true;
            });
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);
        $imageTilerService = Mockery::mock(ImageTilerService::class);
        $imageTilerService->shouldNotReceive('breakIntoTiles');

        (new MapTileGenerationService($imageTilerService, Mockery::mock(MapBackupAssetService::class)))->commitReplacement($replacement);

        $this->assertSame([
            ['test map-pieces', 'test map-pieces-backup'],
            ['test map-pieces-replacement', 'test map-pieces'],
        ], $moves);
    }

    public function test_failed_promotion_restores_the_preserved_current_directory(): void
    {
        $replacement = new PreparedMapTileReplacement([['fresh.png']], 'test map-pieces', 'test map-pieces', 'test map-pieces-replacement', 'test map-pieces-backup', true, true);
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('move')->once()->ordered()->with('test map-pieces', 'test map-pieces-backup')->andReturn(true);
        $disk->shouldReceive('move')->once()->ordered()->with('test map-pieces-replacement', 'test map-pieces')->andReturn(false);
        $disk->shouldReceive('move')->once()->ordered()->with('test map-pieces-backup', 'test map-pieces')->andReturn(true);
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);
        $imageTilerService = Mockery::mock(ImageTilerService::class);
        $imageTilerService->shouldNotReceive('breakIntoTiles');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to commit replacement Game Map tile directory.');

        (new MapTileGenerationService($imageTilerService, Mockery::mock(MapBackupAssetService::class)))->commitReplacement($replacement);
    }

    public function test_finalization_removes_the_previous_renamed_directory(): void
    {
        $replacement = new PreparedMapTileReplacement([['fresh.png']], 'original map-pieces', 'renamed map-pieces', 'renamed map-pieces-replacement', 'renamed map-pieces-backup', false, false);
        $deletedDirectories = [];
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('exists')->once()->with('original map-pieces')->andReturn(true);
        $disk->shouldReceive('deleteDirectory')->once()->with('original map-pieces')
            ->andReturnUsing(function (string $folderName) use (&$deletedDirectories): bool {
                $deletedDirectories[] = $folderName;

                return true;
            });
        $disk->shouldNotReceive('deleteDirectory')->with('renamed map-pieces');
        $disk->shouldReceive('exists')->once()->with('renamed map-pieces-backup')->andReturn(false);
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);
        $imageTilerService = Mockery::mock(ImageTilerService::class);
        $imageTilerService->shouldNotReceive('breakIntoTiles');

        (new MapTileGenerationService($imageTilerService, Mockery::mock(MapBackupAssetService::class)))->finalizeReplacement($replacement);

        $this->assertSame(['original map-pieces'], $deletedDirectories);
    }

    public function test_finalization_removes_the_backup_directory(): void
    {
        $replacement = new PreparedMapTileReplacement([['fresh.png']], 'test map-pieces', 'test map-pieces', 'test map-pieces-replacement', 'test map-pieces-backup', true, true);
        $deletedDirectories = [];
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('exists')->once()->with('test map-pieces-backup')->andReturn(true);
        $disk->shouldReceive('deleteDirectory')->once()->with('test map-pieces-backup')
            ->andReturnUsing(function (string $folderName) use (&$deletedDirectories): bool {
                $deletedDirectories[] = $folderName;

                return true;
            });
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);
        $imageTilerService = Mockery::mock(ImageTilerService::class);
        $imageTilerService->shouldNotReceive('breakIntoTiles');

        (new MapTileGenerationService($imageTilerService, Mockery::mock(MapBackupAssetService::class)))->finalizeReplacement($replacement);

        $this->assertSame(['test map-pieces-backup'], $deletedDirectories);
    }

    public function test_finalization_failure_never_removes_promoted_current_output(): void
    {
        $replacement = new PreparedMapTileReplacement([['fresh.png']], 'original map-pieces', 'renamed map-pieces', 'renamed map-pieces-replacement', 'renamed map-pieces-backup', false, false);
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('exists')->once()->with('original map-pieces')->andReturn(true);
        $disk->shouldReceive('deleteDirectory')->once()->with('original map-pieces')->andReturn(false);
        $disk->shouldNotReceive('deleteDirectory')->with('renamed map-pieces');
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);
        $imageTilerService = Mockery::mock(ImageTilerService::class);
        $imageTilerService->shouldNotReceive('breakIntoTiles');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to delete Game Map tile directory: original map-pieces.');

        (new MapTileGenerationService($imageTilerService, Mockery::mock(MapBackupAssetService::class)))->finalizeReplacement($replacement);
    }

    public function test_rollback_removes_promoted_replacement_output(): void
    {
        $gameMap = $this->createGameMap(['tile_map' => [['fresh.png']]]);
        $replacement = new PreparedMapTileReplacement([['fresh.png']], 'original map-pieces', 'renamed map-pieces', 'renamed map-pieces-replacement', 'renamed map-pieces-backup', false, false);
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('exists')->once()->with('renamed map-pieces')->andReturn(true);
        $disk->shouldReceive('deleteDirectory')->once()->with('renamed map-pieces')->andReturn(true);
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);
        $imageTilerService = Mockery::mock(ImageTilerService::class);
        $imageTilerService->shouldNotReceive('breakIntoTiles');

        (new MapTileGenerationService($imageTilerService, Mockery::mock(MapBackupAssetService::class)))->rollbackReplacement($gameMap, [['old.png']], $replacement);

        $this->assertSame([['old.png']], $gameMap->tile_map);
    }

    public function test_rollback_restores_the_backup_directory(): void
    {
        $gameMap = $this->createGameMap(['tile_map' => [['fresh.png']]]);
        $replacement = new PreparedMapTileReplacement([['fresh.png']], 'test map-pieces', 'test map-pieces', 'test map-pieces-replacement', 'test map-pieces-backup', true, true);
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('exists')->once()->with('test map-pieces')->andReturn(false);
        $disk->shouldReceive('move')->once()->with('test map-pieces-backup', 'test map-pieces')->andReturn(true);
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);
        $imageTilerService = Mockery::mock(ImageTilerService::class);
        $imageTilerService->shouldNotReceive('breakIntoTiles');

        (new MapTileGenerationService($imageTilerService, Mockery::mock(MapBackupAssetService::class)))->rollbackReplacement($gameMap, [['old.png']], $replacement);

        $this->assertSame([['old.png']], $gameMap->tile_map);
    }

    public function test_rollback_restores_the_old_in_memory_tile_map(): void
    {
        $gameMap = $this->createGameMap(['tile_map' => [['fresh.png']]]);
        $replacement = new PreparedMapTileReplacement([['fresh.png']], 'original map-pieces', 'renamed map-pieces', 'renamed map-pieces-replacement', 'renamed map-pieces-backup', false, false);
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('exists')->once()->with('renamed map-pieces')->andReturn(false);
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);
        $imageTilerService = Mockery::mock(ImageTilerService::class);
        $imageTilerService->shouldNotReceive('breakIntoTiles');

        (new MapTileGenerationService($imageTilerService, Mockery::mock(MapBackupAssetService::class)))->rollbackReplacement($gameMap, [['old.png']], $replacement);

        $this->assertSame([['old.png']], $gameMap->tile_map);
    }

    public function test_rollback_reports_promoted_deletion_and_backup_restoration_failures(): void
    {
        $gameMap = $this->createGameMap(['tile_map' => [['fresh.png']]]);
        $replacement = new PreparedMapTileReplacement([['fresh.png']], 'test map-pieces', 'test map-pieces', 'test map-pieces-replacement', 'test map-pieces-backup', true, true);
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('exists')->once()->with('test map-pieces')->andReturn(true);
        $disk->shouldReceive('deleteDirectory')->once()->with('test map-pieces')->andReturn(false);
        $disk->shouldReceive('move')->once()->with('test map-pieces-backup', 'test map-pieces')->andReturn(false);
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);
        $imageTilerService = Mockery::mock(ImageTilerService::class);
        $imageTilerService->shouldNotReceive('breakIntoTiles');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Failed to delete Game Map tile directory: test map-pieces\..*Failed to restore the current Game Map tile directory\./');

        (new MapTileGenerationService($imageTilerService, Mockery::mock(MapBackupAssetService::class)))->rollbackReplacement($gameMap, [['old.png']], $replacement);
    }

    public function test_renamed_rollback_preserves_the_previous_name_directory(): void
    {
        $gameMap = $this->createGameMap(['tile_map' => [['fresh.png']]]);
        $replacement = new PreparedMapTileReplacement([['fresh.png']], 'original map-pieces', 'renamed map-pieces', 'renamed map-pieces-replacement', 'renamed map-pieces-backup', false, false);
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('exists')->once()->with('renamed map-pieces')->andReturn(true);
        $disk->shouldReceive('deleteDirectory')->once()->with('renamed map-pieces')->andReturn(true);
        $disk->shouldNotReceive('deleteDirectory')->with('original map-pieces');
        $disk->shouldNotReceive('move');
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);
        $imageTilerService = Mockery::mock(ImageTilerService::class);
        $imageTilerService->shouldNotReceive('breakIntoTiles');

        (new MapTileGenerationService($imageTilerService, Mockery::mock(MapBackupAssetService::class)))->rollbackReplacement($gameMap, [['old.png']], $replacement);

        $this->assertSame([['old.png']], $gameMap->tile_map);
    }

    public function test_same_name_rollback_removes_promoted_output_before_restoring_backup(): void
    {
        $gameMap = $this->createGameMap(['tile_map' => [['fresh.png']]]);
        $replacement = new PreparedMapTileReplacement([['fresh.png']], 'test map-pieces', 'test map-pieces', 'test map-pieces-replacement', 'test map-pieces-backup', true, true);
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('exists')->once()->ordered()->with('test map-pieces')->andReturn(true);
        $disk->shouldReceive('deleteDirectory')->once()->ordered()->with('test map-pieces')->andReturn(true);
        $disk->shouldReceive('move')->once()->ordered()->with('test map-pieces-backup', 'test map-pieces')->andReturn(true);
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);
        $imageTilerService = Mockery::mock(ImageTilerService::class);
        $imageTilerService->shouldNotReceive('breakIntoTiles');

        (new MapTileGenerationService($imageTilerService, Mockery::mock(MapBackupAssetService::class)))->rollbackReplacement($gameMap, [['old.png']], $replacement);

        $this->assertSame([['old.png']], $gameMap->tile_map);
    }

    public function test_failed_required_directory_deletion_is_surfaced(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Test Map']);
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('exists')->once()->with('test map-pieces')->andReturn(true);
        $disk->shouldReceive('deleteDirectory')->once()->with('test map-pieces')->andReturn(false);
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);
        $imageTilerService = Mockery::mock(ImageTilerService::class);
        $imageTilerService->shouldNotReceive('breakIntoTiles');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to delete Game Map tile directory');

        (new MapTileGenerationService($imageTilerService, Mockery::mock(MapBackupAssetService::class)))->remove($gameMap);
    }
}
