<?php

namespace Tests\Unit\Flare\MapGenerator\Services;

use App\Flare\MapGenerator\Services\ImageTilerService;
use App\Flare\MapGenerator\Services\MapTileGenerationService;
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

    public function test_tile_skips_when_the_committed_tile_directory_already_exists(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Test Map', 'tile_map' => [['existing-tile.png']]]);
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('exists')->once()->with('test map-pieces')->andReturn(true);
        $disk->shouldNotReceive('path');
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);
        $imageTilerService = Mockery::mock(ImageTilerService::class);
        $imageTilerService->shouldNotReceive('breakIntoTiles');

        (new MapTileGenerationService($imageTilerService))->tile($gameMap);

        $this->assertSame([['existing-tile.png']], $gameMap->fresh()->tile_map);
    }

    public function test_tile_generates_and_persists_missing_committed_tiles(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Test Map', 'path' => 'test-map.png']);
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('exists')->once()->with('test map-pieces')->andReturn(false);
        $disk->shouldReceive('path')->once()->with('test-map.png')->andReturn('/tmp/test-map.png');
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);
        $imageTilerService = Mockery::mock(ImageTilerService::class);
        $imageTilerService->shouldReceive('breakIntoTiles')->once()
            ->with('/tmp/test-map.png', 'test map-pieces', 'test map-pieces')
            ->andReturn([['tile.png']]);

        (new MapTileGenerationService($imageTilerService))->tile($gameMap);

        $this->assertSame([['tile.png']], $gameMap->fresh()->tile_map);
    }

    public function test_tile_removes_stale_partial_output_before_regenerating_when_tile_map_is_null(): void
    {
        $gameMap = $this->createGameMap([
            'name' => 'Test Map',
            'path' => 'test-map.png',
            'tile_map' => null,
        ]);
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('exists')->once()->ordered()->with('test map-pieces')->andReturn(true);
        $disk->shouldReceive('deleteDirectory')->once()->ordered()->with('test map-pieces')->andReturn(true);
        $disk->shouldReceive('path')->once()->ordered()->with('test-map.png')->andReturn('/tmp/test-map.png');
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);
        $imageTilerService = Mockery::mock(ImageTilerService::class);
        $imageTilerService->shouldReceive('breakIntoTiles')->once()
            ->with('/tmp/test-map.png', 'test map-pieces', 'test map-pieces')
            ->andReturn([['fresh-tile.png']]);

        (new MapTileGenerationService($imageTilerService))->tile($gameMap);

        $this->assertSame([['fresh-tile.png']], $gameMap->fresh()->tile_map);
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

        $replacement = (new MapTileGenerationService($imageTilerService))->prepareReplacement($gameMap, 'Original Map');

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

        (new MapTileGenerationService($imageTilerService))->prepareReplacement($gameMap, 'Test Map');
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

        (new MapTileGenerationService($imageTilerService))->prepareReplacement($gameMap, 'Test Map');
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

        (new MapTileGenerationService($imageTilerService))->commitReplacement($replacement);

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

        (new MapTileGenerationService($imageTilerService))->commitReplacement($replacement);
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

        (new MapTileGenerationService($imageTilerService))->finalizeReplacement($replacement);

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

        (new MapTileGenerationService($imageTilerService))->finalizeReplacement($replacement);

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

        (new MapTileGenerationService($imageTilerService))->finalizeReplacement($replacement);
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

        (new MapTileGenerationService($imageTilerService))->rollbackReplacement($gameMap, [['old.png']], $replacement);

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

        (new MapTileGenerationService($imageTilerService))->rollbackReplacement($gameMap, [['old.png']], $replacement);

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

        (new MapTileGenerationService($imageTilerService))->rollbackReplacement($gameMap, [['old.png']], $replacement);

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

        (new MapTileGenerationService($imageTilerService))->rollbackReplacement($gameMap, [['old.png']], $replacement);
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

        (new MapTileGenerationService($imageTilerService))->rollbackReplacement($gameMap, [['old.png']], $replacement);

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

        (new MapTileGenerationService($imageTilerService))->rollbackReplacement($gameMap, [['old.png']], $replacement);

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

        (new MapTileGenerationService($imageTilerService))->remove($gameMap);
    }
}
