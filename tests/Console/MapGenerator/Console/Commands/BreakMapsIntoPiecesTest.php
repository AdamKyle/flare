<?php

namespace Tests\Console\MapGenerator\Console\Commands;

use App\Flare\MapGenerator\Services\MapTileGenerationService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;

class BreakMapsIntoPiecesTest extends TestCase
{
    use CreateGameMap, RefreshDatabase;

    private ?string $tempImagePath = null;

    protected function tearDown(): void
    {
        if ($this->tempImagePath !== null && file_exists($this->tempImagePath)) {
            unlink($this->tempImagePath);
        }

        parent::tearDown();
    }

    public function test_it_skips_a_map_whose_pieces_already_exist(): void
    {
        $this->createGameMap(['name' => 'Existing Map', 'path' => 'existing-map.png']);

        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('exists')->once()->with('existing map-pieces')->andReturn(true);
        $disk->shouldNotReceive('path');

        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);

        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $this->app->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $this->artisan('break:maps-into-pieces')
            ->expectsOutputToContain('Skipping Existing Map, pieces already exist.')
            ->assertExitCode(0);
    }

    public function test_it_reports_an_error_when_the_image_file_is_missing(): void
    {
        $this->createGameMap(['name' => 'Missing Image Map', 'path' => 'missing.png']);

        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('exists')->once()->with('missing image map-pieces')->andReturn(false);
        $disk->shouldReceive('path')->once()->with('missing.png')->andReturn('/tmp/does-not-exist-map.png');

        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);

        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $this->app->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $this->artisan('break:maps-into-pieces')
            ->expectsOutputToContain('Image file for Missing Image Map not found at: /tmp/does-not-exist-map.png')
            ->assertExitCode(0);
    }

    public function test_it_tiles_a_map_whose_image_exists(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Ready Map', 'path' => 'ready-map.png']);

        $this->tempImagePath = tempnam(sys_get_temp_dir(), 'ready-map');
        file_put_contents($this->tempImagePath, 'fake-image-data');

        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('exists')->once()->with('ready map-pieces')->andReturn(false);
        $disk->shouldReceive('path')->once()->with('ready-map.png')->andReturn($this->tempImagePath);

        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);

        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldReceive('tile')->once()->with(Mockery::on(fn ($map) => $map->id === $gameMap->id));
        $this->app->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $this->artisan('break:maps-into-pieces')
            ->expectsOutputToContain('Successfully chopped Ready Map into tiles.')
            ->assertExitCode(0);
    }
}
