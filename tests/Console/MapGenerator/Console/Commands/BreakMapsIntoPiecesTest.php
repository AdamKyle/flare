<?php

namespace Tests\Console\MapGenerator\Console\Commands;

use App\Flare\MapGenerator\Services\MapBackupAssetService;
use App\Flare\MapGenerator\Services\MapTileGenerationService;
use App\Flare\MapGenerator\Values\MapBackupAssetResult;
use App\Flare\MapGenerator\Values\MapBackupAssetStatus;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;

class BreakMapsIntoPiecesTest extends TestCase
{
    use CreateGameMap, RefreshDatabase;

    public function test_default_mode_restores_and_reports_success(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Restored Map']);

        $mapBackupAssetService = Mockery::mock(MapBackupAssetService::class);
        $mapBackupAssetService->shouldReceive('restore')->once()
            ->with(Mockery::on(fn ($map) => $map->id === $gameMap->id))
            ->andReturn(new MapBackupAssetResult(MapBackupAssetStatus::RESTORED, 'Restored committed tile pieces for: Restored Map'));
        $this->app->instance(MapBackupAssetService::class, $mapBackupAssetService);

        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $this->app->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $this->artisan('break:maps-into-pieces')
            ->expectsOutputToContain('Restored committed tile pieces for: Restored Map')
            ->expectsOutputToContain('Restored: 1')
            ->assertExitCode(0);
    }

    public function test_default_mode_never_generates_when_backup_is_missing_and_reports_failure(): void
    {
        $this->createGameMap(['name' => 'Missing Backup Map']);

        $mapBackupAssetService = Mockery::mock(MapBackupAssetService::class);
        $mapBackupAssetService->shouldReceive('restore')->once()
            ->andReturn(new MapBackupAssetResult(
                MapBackupAssetStatus::MISSING_BACKUP,
                'Missing committed tile pieces backup for Missing Backup Map at: resources/backup/maps/missing backup map-pieces',
            ));
        $this->app->instance(MapBackupAssetService::class, $mapBackupAssetService);

        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $this->app->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $this->artisan('break:maps-into-pieces')
            ->expectsOutputToContain('Missing committed tile pieces backup for Missing Backup Map')
            ->assertExitCode(1);
    }

    public function test_default_mode_processes_every_map_before_reporting_failure(): void
    {
        $this->createGameMap(['name' => 'Valid Map']);
        $this->createGameMap(['name' => 'Missing Map']);

        $mapBackupAssetService = Mockery::mock(MapBackupAssetService::class);
        $mapBackupAssetService->shouldReceive('restore')->twice()->andReturn(
            new MapBackupAssetResult(MapBackupAssetStatus::ALREADY_VALID, 'Live tiles already valid for: Valid Map'),
            new MapBackupAssetResult(MapBackupAssetStatus::MISSING_BACKUP, 'Missing committed tile pieces backup for: Missing Map'),
        );
        $this->app->instance(MapBackupAssetService::class, $mapBackupAssetService);

        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $this->app->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $this->artisan('break:maps-into-pieces')
            ->expectsOutputToContain('Already valid: 1')
            ->expectsOutputToContain('Missing backup: 1')
            ->assertExitCode(1);
    }

    public function test_generate_missing_option_generates_only_for_maps_missing_backup_and_live_assets(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Generate Me', 'path' => 'generate-me.png']);

        $mapBackupAssetService = Mockery::mock(MapBackupAssetService::class);
        $mapBackupAssetService->shouldReceive('restore')->once()
            ->andReturn(new MapBackupAssetResult(MapBackupAssetStatus::MISSING_BACKUP, 'Missing committed tile pieces backup for: Generate Me'));
        $this->app->instance(MapBackupAssetService::class, $mapBackupAssetService);

        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('exists')->once()->with('generate-me.png')->andReturn(true);
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);

        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldReceive('tile')->once()
            ->with(Mockery::on(fn ($map) => $map->id === $gameMap->id), true);
        $this->app->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $this->artisan('break:maps-into-pieces --generate-missing')
            ->expectsOutputToContain('Generated tile pieces for: Generate Me')
            ->assertExitCode(0);
    }

    public function test_generate_missing_option_does_not_generate_for_an_already_valid_map(): void
    {
        $this->createGameMap(['name' => 'Already Fine']);

        $mapBackupAssetService = Mockery::mock(MapBackupAssetService::class);
        $mapBackupAssetService->shouldReceive('restore')->once()
            ->andReturn(new MapBackupAssetResult(MapBackupAssetStatus::ALREADY_VALID, 'Live tiles already valid for: Already Fine'));
        $this->app->instance(MapBackupAssetService::class, $mapBackupAssetService);

        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $this->app->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $this->artisan('break:maps-into-pieces --generate-missing')
            ->expectsOutputToContain('Already valid: 1')
            ->assertExitCode(0);
    }
}
