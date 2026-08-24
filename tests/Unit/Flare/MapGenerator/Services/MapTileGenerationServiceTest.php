<?php

namespace Tests\Unit\Flare\MapGenerator\Services;

use App\Flare\MapGenerator\Services\ImageTilerService;
use App\Flare\MapGenerator\Services\MapTileGenerationService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;

class MapTileGenerationServiceTest extends TestCase
{
    use CreateGameMap, RefreshDatabase;

    public function test_tile_skips_when_the_pieces_folder_already_exists(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Test Map', 'path' => 'test-map.png']);

        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('exists')->once()->with('test map-pieces')->andReturn(true);
        $disk->shouldNotReceive('path');

        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);

        $imageTilerService = Mockery::mock(ImageTilerService::class);
        $imageTilerService->shouldNotReceive('breakIntoTiles');

        (new MapTileGenerationService($imageTilerService))->tile($gameMap);

        $this->assertNull($gameMap->fresh()->tile_map);
    }

    public function test_tile_breaks_the_image_into_tiles_and_stores_the_tile_map(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Test Map', 'path' => 'test-map.png']);

        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('exists')->once()->with('test map-pieces')->andReturn(false);
        $disk->shouldReceive('path')->once()->with('test-map.png')->andReturn('/tmp/test-map.png');

        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);

        $imageTilerService = Mockery::mock(ImageTilerService::class);
        $imageTilerService->shouldReceive('breakIntoTiles')
            ->once()
            ->with('/tmp/test-map.png', 'test map-pieces')
            ->andReturn([['tile_0_0.png']]);

        (new MapTileGenerationService($imageTilerService))->tile($gameMap);

        $this->assertSame([['tile_0_0.png']], $gameMap->fresh()->tile_map);
    }
}
