<?php

namespace Tests\Unit\Flare\MapGenerator\Services;

use App\Flare\MapGenerator\Services\MapBackupAssetService;
use App\Flare\MapGenerator\Services\MapTileMapBuilder;
use App\Flare\MapGenerator\Values\MapBackupAssetStatus;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;

class MapBackupAssetServiceTest extends TestCase
{
    use CreateGameMap, RefreshDatabase;

    private const BACKUP_ROOT = '/backup/maps';

    public function test_restore_reports_already_valid_when_live_pieces_and_tile_map_exist(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Test Map', 'tile_map' => [['existing.png']]]);

        $liveDisk = Mockery::mock(FilesystemAdapter::class);
        $liveDisk->shouldReceive('exists')->once()->with('test map-pieces')->andReturn(true);
        Storage::shouldReceive('disk')->with('maps')->andReturn($liveDisk);
        Storage::shouldNotReceive('build');

        $mapTileMapBuilder = Mockery::mock(MapTileMapBuilder::class);
        $mapTileMapBuilder->shouldNotReceive('build');

        $result = (new MapBackupAssetService($mapTileMapBuilder, self::BACKUP_ROOT))->restore($gameMap);

        $this->assertSame(MapBackupAssetStatus::ALREADY_VALID, $result->status);
        $this->assertSame([['existing.png']], $gameMap->fresh()->tile_map);
    }

    public function test_restore_repairs_tile_map_from_existing_live_pieces_without_deleting_them(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Repair Map', 'tile_map' => null]);
        $folder = 'repair map-pieces';

        $liveDisk = Mockery::mock(FilesystemAdapter::class);
        $liveDisk->shouldReceive('exists')->once()->with($folder)->andReturn(true);
        $liveDisk->shouldNotReceive('deleteDirectory');
        Storage::shouldReceive('disk')->with('maps')->andReturn($liveDisk);
        Storage::shouldNotReceive('build');

        $mapTileMapBuilder = Mockery::mock(MapTileMapBuilder::class);
        $mapTileMapBuilder->shouldReceive('build')->once()->with($folder)->andReturn([['repaired.png']]);

        $result = (new MapBackupAssetService($mapTileMapBuilder, self::BACKUP_ROOT))->restore($gameMap);

        $this->assertSame(MapBackupAssetStatus::REPAIRED, $result->status);
        $this->assertSame([['repaired.png']], $gameMap->fresh()->tile_map);
    }

    public function test_restore_copies_committed_pieces_and_reconstructs_tile_map_when_live_pieces_are_missing(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Restore Map']);
        $folder = 'restore map-pieces';
        $tileFile = "{$folder}/{$folder}_tile_0_0.png";

        $liveDisk = Mockery::mock(FilesystemAdapter::class);
        $liveDisk->shouldReceive('exists')->once()->with($folder)->andReturn(false);
        $liveDisk->shouldReceive('put')->once()->with($tileFile, 'tile-bytes')->andReturn(true);
        Storage::shouldReceive('disk')->with('maps')->andReturn($liveDisk);

        $backupDisk = Mockery::mock(FilesystemAdapter::class);
        $backupDisk->shouldReceive('exists')->once()->with($folder)->andReturn(true);
        $backupDisk->shouldReceive('allFiles')->once()->with($folder)->andReturn([$tileFile]);
        $backupDisk->shouldReceive('get')->once()->with($tileFile)->andReturn('tile-bytes');
        Storage::shouldReceive('build')->once()->with(['driver' => 'local', 'root' => self::BACKUP_ROOT])->andReturn($backupDisk);

        $mapTileMapBuilder = Mockery::mock(MapTileMapBuilder::class);
        $mapTileMapBuilder->shouldReceive('build')->once()->with($folder)->andReturn([['restored.png']]);

        $result = (new MapBackupAssetService($mapTileMapBuilder, self::BACKUP_ROOT))->restore($gameMap);

        $this->assertSame(MapBackupAssetStatus::RESTORED, $result->status);
        $this->assertSame([['restored.png']], $gameMap->fresh()->tile_map);
    }

    public function test_restore_copies_committed_generated_gem_world_image_when_live_image_is_missing(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);
        $gameMap = $this->createGameMap([
            'name' => 'Fiery World Gem World',
            'path' => 'generated-gem-worlds/fiery-world-gem-world.png',
            'generated_map_type' => 'map_gem',
            'generated_parent_game_map_id' => $parentMap->id,
            'tile_map' => [['existing.png']],
        ]);
        $folder = 'fiery world gem world-pieces';

        $liveDisk = Mockery::mock(FilesystemAdapter::class);
        $liveDisk->shouldReceive('exists')->once()->with($gameMap->path)->andReturn(false);
        $liveDisk->shouldReceive('put')->once()->with($gameMap->path, 'image-bytes')->andReturn(true);
        $liveDisk->shouldReceive('exists')->once()->with($folder)->andReturn(true);
        Storage::shouldReceive('disk')->with('maps')->andReturn($liveDisk);

        $backupDisk = Mockery::mock(FilesystemAdapter::class);
        $backupDisk->shouldReceive('exists')->once()->with($gameMap->path)->andReturn(true);
        $backupDisk->shouldReceive('get')->once()->with($gameMap->path)->andReturn('image-bytes');
        Storage::shouldReceive('build')->once()->with(['driver' => 'local', 'root' => self::BACKUP_ROOT])->andReturn($backupDisk);

        $mapTileMapBuilder = Mockery::mock(MapTileMapBuilder::class);
        $mapTileMapBuilder->shouldNotReceive('build');

        $result = (new MapBackupAssetService($mapTileMapBuilder, self::BACKUP_ROOT))->restore($gameMap);

        $this->assertSame(MapBackupAssetStatus::ALREADY_VALID, $result->status);
    }

    public function test_restore_reports_missing_backup_and_does_not_create_live_pieces(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Missing Map']);
        $folder = 'missing map-pieces';

        $liveDisk = Mockery::mock(FilesystemAdapter::class);
        $liveDisk->shouldReceive('exists')->once()->with($folder)->andReturn(false);
        $liveDisk->shouldNotReceive('put');
        Storage::shouldReceive('disk')->with('maps')->andReturn($liveDisk);

        $backupDisk = Mockery::mock(FilesystemAdapter::class);
        $backupDisk->shouldReceive('exists')->once()->with($folder)->andReturn(false);
        $backupDisk->shouldNotReceive('allFiles');
        Storage::shouldReceive('build')->once()->andReturn($backupDisk);

        $mapTileMapBuilder = Mockery::mock(MapTileMapBuilder::class);
        $mapTileMapBuilder->shouldNotReceive('build');

        $result = (new MapBackupAssetService($mapTileMapBuilder, self::BACKUP_ROOT))->restore($gameMap);

        $this->assertSame(MapBackupAssetStatus::MISSING_BACKUP, $result->status);
        $this->assertNull($gameMap->fresh()->tile_map);
    }

    public function test_restore_reports_failure_when_reconstructed_tile_map_is_invalid(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Broken Map', 'tile_map' => null]);
        $folder = 'broken map-pieces';

        $liveDisk = Mockery::mock(FilesystemAdapter::class);
        $liveDisk->shouldReceive('exists')->once()->with($folder)->andReturn(true);
        Storage::shouldReceive('disk')->with('maps')->andReturn($liveDisk);
        Storage::shouldNotReceive('build');

        $mapTileMapBuilder = Mockery::mock(MapTileMapBuilder::class);
        $mapTileMapBuilder->shouldReceive('build')->once()->with($folder)->andReturn(null);

        $result = (new MapBackupAssetService($mapTileMapBuilder, self::BACKUP_ROOT))->restore($gameMap);

        $this->assertSame(MapBackupAssetStatus::FAILED, $result->status);
        $this->assertNull($gameMap->fresh()->tile_map);
    }
}
