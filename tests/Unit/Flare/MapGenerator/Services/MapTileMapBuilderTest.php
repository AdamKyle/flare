<?php

namespace Tests\Unit\Flare\MapGenerator\Services;

use App\Flare\MapGenerator\Services\MapTileMapBuilder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class MapTileMapBuilderTest extends TestCase
{
    public function test_build_returns_a_row_major_tile_map_sorted_by_y_then_x(): void
    {
        $folder = 'sample-pieces';
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('files')->once()->with($folder)->andReturn([
            "{$folder}/{$folder}_tile_250_0.png",
            "{$folder}/{$folder}_tile_0_0.png",
            "{$folder}/{$folder}_tile_250_250.png",
            "{$folder}/{$folder}_tile_0_250.png",
        ]);
        $disk->shouldReceive('url')->andReturnUsing(fn (string $path): string => 'https://cdn.test/storage/'.$path);
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);

        $tileMap = (new MapTileMapBuilder)->build($folder);

        $this->assertSame([
            [
                "https://cdn.test/storage/{$folder}/{$folder}_tile_0_0.png",
                "https://cdn.test/storage/{$folder}/{$folder}_tile_250_0.png",
            ],
            [
                "https://cdn.test/storage/{$folder}/{$folder}_tile_0_250.png",
                "https://cdn.test/storage/{$folder}/{$folder}_tile_250_250.png",
            ],
        ], $tileMap);
    }

    public function test_build_ignores_files_not_matching_the_tile_naming_convention(): void
    {
        $folder = 'ignore-pieces';
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('files')->once()->with($folder)->andReturn([
            "{$folder}/{$folder}_tile_0_0.png",
            "{$folder}/readme.txt",
        ]);
        $disk->shouldReceive('url')->once()->with("{$folder}/{$folder}_tile_0_0.png")->andReturn('https://cdn.test/storage/tile.png');
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);

        $tileMap = (new MapTileMapBuilder)->build($folder);

        $this->assertSame([['https://cdn.test/storage/tile.png']], $tileMap);
    }

    public function test_build_returns_null_when_no_tiles_exist(): void
    {
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('files')->once()->with('empty-pieces')->andReturn([]);
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);

        $this->assertNull((new MapTileMapBuilder)->build('empty-pieces'));
    }

    public function test_build_rejects_rows_with_inconsistent_column_counts(): void
    {
        $folder = 'inconsistent-pieces';
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('files')->once()->with($folder)->andReturn([
            "{$folder}/{$folder}_tile_0_0.png",
            "{$folder}/{$folder}_tile_250_0.png",
            "{$folder}/{$folder}_tile_0_250.png",
        ]);
        $disk->shouldNotReceive('url');
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);

        $this->assertNull((new MapTileMapBuilder)->build($folder));
    }
}
