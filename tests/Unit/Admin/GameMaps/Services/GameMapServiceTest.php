<?php

namespace Tests\Unit\Admin\GameMaps\Services;

use App\Admin\GameMaps\Services\GameMapService;
use App\Flare\MapGenerator\Services\MapTileGenerationService;
use App\Flare\MapGenerator\Values\PreparedMapTileReplacement;
use App\Flare\Models\GameMap;
use App\Game\Maps\Contracts\CoordinatesQuery;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;

class GameMapServiceTest extends TestCase
{
    use CreateGameMap, RefreshDatabase;

    public function test_initial_tile_processing_returns_success_after_generation(): void
    {
        $gameMap = $this->createGameMap();
        $tileService = Mockery::mock(MapTileGenerationService::class);
        $tileService->shouldReceive('tile')->once()->with($gameMap);
        $tileService->shouldNotReceive('remove');
        $service = new GameMapService(Mockery::mock(CoordinatesQuery::class));

        $this->assertTrue($service->processInitialTiles($gameMap, $tileService));
    }

    public function test_initial_tile_processing_failure_removes_partial_output(): void
    {
        $gameMap = $this->createGameMap();
        $tileService = Mockery::mock(MapTileGenerationService::class);
        $tileService->shouldReceive('tile')->once()->with($gameMap)
            ->andThrow(new RuntimeException('generation failed'));
        $tileService->shouldReceive('remove')->once()->with($gameMap);
        Log::shouldReceive('error')->once()->with('Initial Game Map tile generation failed.', Mockery::type('array'));
        $service = new GameMapService(Mockery::mock(CoordinatesQuery::class));

        $this->assertFalse($service->processInitialTiles($gameMap, $tileService));
    }

    public function test_initial_cleanup_failure_cannot_report_success(): void
    {
        $gameMap = $this->createGameMap();
        $tileService = Mockery::mock(MapTileGenerationService::class);
        $tileService->shouldReceive('tile')->once()->andThrow(new RuntimeException('generation failed'));
        $tileService->shouldReceive('remove')->once()->andThrow(new RuntimeException('cleanup failed'));
        Log::shouldReceive('error')->twice();
        $service = new GameMapService(Mockery::mock(CoordinatesQuery::class));

        $this->assertFalse($service->processInitialTiles($gameMap, $tileService));
    }

    public function test_replacement_processing_promotes_persists_finalizes_and_removes_previous_source(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Renamed', 'path' => 'renamed/new.png', 'tile_map' => null]);
        $replacement = new PreparedMapTileReplacement(
            [['fresh.png']],
            'original-pieces',
            'renamed-pieces',
            'renamed-pieces-replacement',
            'renamed-pieces-backup',
            true,
            false,
        );
        $tileService = Mockery::mock(MapTileGenerationService::class);
        $tileService->shouldReceive('prepareReplacement')->once()->with($gameMap, 'Original')->andReturn($replacement);
        $tileService->shouldReceive('commitReplacement')->once()->ordered()->with($replacement);
        $tileService->shouldReceive('finalizeReplacement')->once()->ordered()->with($replacement);
        $tileService->shouldNotReceive('rollbackReplacement');
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('delete')->once()->with('original/old.png')->andReturn(true);
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);
        $service = new GameMapService(Mockery::mock(CoordinatesQuery::class));

        $this->assertTrue($service->processReplacementTiles($gameMap, 'Original', 'original/old.png', $tileService));
        $this->assertSame([['fresh.png']], $gameMap->fresh()->tile_map);
    }

    public function test_replacement_preparation_failure_returns_failure(): void
    {
        $gameMap = $this->createGameMap(['tile_map' => null]);
        $tileService = Mockery::mock(MapTileGenerationService::class);
        $tileService->shouldReceive('prepareReplacement')->once()->andThrow(new RuntimeException('prepare failed'));
        $tileService->shouldNotReceive('commitReplacement');
        $tileService->shouldNotReceive('finalizeReplacement');
        $tileService->shouldNotReceive('rollbackReplacement');
        Log::shouldReceive('error')->once();
        $service = new GameMapService(Mockery::mock(CoordinatesQuery::class));

        $this->assertFalse($service->processReplacementTiles($gameMap, 'Original', 'original.png', $tileService));
    }

    public function test_replacement_commit_failure_returns_failure_without_rollback(): void
    {
        $gameMap = $this->createGameMap(['tile_map' => null]);
        $replacement = new PreparedMapTileReplacement([['fresh.png']], 'old', 'new', 'replacement', 'backup', true, false);
        $tileService = Mockery::mock(MapTileGenerationService::class);
        $tileService->shouldReceive('prepareReplacement')->once()->andReturn($replacement);
        $tileService->shouldReceive('commitReplacement')->once()->andThrow(new RuntimeException('commit failed'));
        $tileService->shouldNotReceive('rollbackReplacement');
        $tileService->shouldNotReceive('finalizeReplacement');
        Log::shouldReceive('error')->once();
        $service = new GameMapService(Mockery::mock(CoordinatesQuery::class));

        $this->assertFalse($service->processReplacementTiles($gameMap, 'Original', $gameMap->path, $tileService));
    }

    public function test_replacement_persistence_failure_attempts_rollback(): void
    {
        $gameMap = $this->createGameMap(['tile_map' => null]);
        $replacement = new PreparedMapTileReplacement([['fresh.png']], 'old', 'new', 'replacement', 'backup', true, false);
        $tileService = Mockery::mock(MapTileGenerationService::class);
        $tileService->shouldReceive('prepareReplacement')->once()->andReturn($replacement);
        $tileService->shouldReceive('commitReplacement')->once();
        $tileService->shouldReceive('rollbackReplacement')->once()->with($gameMap, null, $replacement);
        $tileService->shouldNotReceive('finalizeReplacement');
        Log::shouldReceive('error')->once();
        $originalEventDispatcher = GameMap::getEventDispatcher();
        $this->assertNotNull($originalEventDispatcher);
        GameMap::setEventDispatcher(clone $originalEventDispatcher);
        GameMap::saving(fn (GameMap $savingMap): bool => $savingMap->tile_map !== [['fresh.png']]);
        $service = new GameMapService(Mockery::mock(CoordinatesQuery::class));

        $result = $service->processReplacementTiles($gameMap, 'Original', $gameMap->path, $tileService);
        GameMap::setEventDispatcher($originalEventDispatcher);

        $this->assertFalse($result);
    }

    public function test_replacement_rollback_failure_is_logged_and_cannot_report_success(): void
    {
        $gameMap = $this->createGameMap(['tile_map' => null]);
        $replacement = new PreparedMapTileReplacement([['fresh.png']], 'old', 'new', 'replacement', 'backup', true, false);
        $tileService = Mockery::mock(MapTileGenerationService::class);
        $tileService->shouldReceive('prepareReplacement')->once()->andReturn($replacement);
        $tileService->shouldReceive('commitReplacement')->once();
        $tileService->shouldReceive('rollbackReplacement')->once()->andThrow(new RuntimeException('rollback failed'));
        $tileService->shouldNotReceive('finalizeReplacement');
        Log::shouldReceive('error')->twice();
        $originalEventDispatcher = GameMap::getEventDispatcher();
        $this->assertNotNull($originalEventDispatcher);
        GameMap::setEventDispatcher(clone $originalEventDispatcher);
        GameMap::saving(fn (GameMap $savingMap): bool => $savingMap->tile_map !== [['fresh.png']]);
        $service = new GameMapService(Mockery::mock(CoordinatesQuery::class));

        $result = $service->processReplacementTiles($gameMap, 'Original', $gameMap->path, $tileService);
        GameMap::setEventDispatcher($originalEventDispatcher);

        $this->assertFalse($result);
    }

    public function test_finalization_failure_does_not_revert_authoritative_replacement(): void
    {
        $gameMap = $this->createGameMap(['path' => 'current.png', 'tile_map' => null]);
        $replacement = new PreparedMapTileReplacement([['fresh.png']], 'old', 'new', 'replacement', 'backup', true, false);
        $tileService = Mockery::mock(MapTileGenerationService::class);
        $tileService->shouldReceive('prepareReplacement')->once()->andReturn($replacement);
        $tileService->shouldReceive('commitReplacement')->once();
        $tileService->shouldReceive('finalizeReplacement')->once()->andThrow(new RuntimeException('finalize failed'));
        $tileService->shouldNotReceive('rollbackReplacement');
        Log::shouldReceive('error')->once();
        $service = new GameMapService(Mockery::mock(CoordinatesQuery::class));

        $this->assertTrue($service->processReplacementTiles($gameMap, 'Original', 'current.png', $tileService));
        $this->assertSame([['fresh.png']], $gameMap->fresh()->tile_map);
    }

    public function test_previous_source_cleanup_failure_does_not_revert_authoritative_replacement(): void
    {
        $gameMap = $this->createGameMap(['path' => 'current.png', 'tile_map' => null]);
        $replacement = new PreparedMapTileReplacement([['fresh.png']], 'old', 'new', 'replacement', 'backup', true, false);
        $tileService = Mockery::mock(MapTileGenerationService::class);
        $tileService->shouldReceive('prepareReplacement')->once()->andReturn($replacement);
        $tileService->shouldReceive('commitReplacement')->once();
        $tileService->shouldReceive('finalizeReplacement')->once();
        $tileService->shouldNotReceive('rollbackReplacement');
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('delete')->once()->with('old.png')->andReturn(false);
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);
        Log::shouldReceive('error')->once();
        $service = new GameMapService(Mockery::mock(CoordinatesQuery::class));

        $this->assertTrue($service->processReplacementTiles($gameMap, 'Original', 'old.png', $tileService));
        $this->assertSame([['fresh.png']], $gameMap->fresh()->tile_map);
    }
}
