<?php

namespace Tests\Unit\Flare\MapGenerator\Services;

use App\Flare\MapGenerator\Services\ImageTilerService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\EncodedImageInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Mockery;
use Tests\TestCase;

class ImageTilerServiceTest extends TestCase
{
    public function test_break_into_tiles_creates_the_folder_and_returns_a_tile_grid(): void
    {
        $encodedSource = Mockery::mock(EncodedImageInterface::class);
        $encodedSource->shouldReceive('toString')->andReturn('source-bytes');

        $encodedTile = Mockery::mock(EncodedImageInterface::class);
        $encodedTile->shouldReceive('toString')->andReturn('tile-bytes');

        $image = Mockery::mock(ImageInterface::class);
        $image->shouldReceive('width')->andReturn(260);
        $image->shouldReceive('height')->andReturn(260);
        $image->shouldReceive('encode')->andReturn($encodedSource);

        $tile = Mockery::mock(ImageInterface::class);
        $tile->shouldReceive('crop')->andReturnSelf();
        $tile->shouldReceive('encode')->andReturn($encodedTile);

        $imageManager = Mockery::mock(ImageManager::class);
        $imageManager->shouldReceive('decodePath')->once()->with('my-image.png')->andReturn($image);
        $imageManager->shouldReceive('decodeBinary')->times(4)->andReturn($tile);

        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('makeDirectory')->once()->with('my-folder');
        $disk->shouldReceive('put')->times(4);
        $disk->shouldReceive('url')->times(4)->andReturnUsing(fn ($path) => "https://example.test/{$path}");

        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);

        $service = new ImageTilerService($imageManager);

        $tileMap = $service->breakIntoTiles('my-image.png', 'my-folder', 'my-folder');

        $this->assertCount(2, $tileMap);
        $this->assertCount(2, $tileMap[0]);
        $this->assertSame('https://example.test/my-folder/my-folder_tile_0_0.png', $tileMap[0][0]);
        $this->assertSame('https://example.test/my-folder/my-folder_tile_250_0.png', $tileMap[0][1]);
        $this->assertSame('https://example.test/my-folder/my-folder_tile_0_250.png', $tileMap[1][0]);
        $this->assertSame('https://example.test/my-folder/my-folder_tile_250_250.png', $tileMap[1][1]);
    }

    public function test_break_into_tiles_produces_a_single_tile_for_an_image_smaller_than_the_tile_size(): void
    {
        $encodedSource = Mockery::mock(EncodedImageInterface::class);
        $encodedSource->shouldReceive('toString')->andReturn('source-bytes');

        $encodedTile = Mockery::mock(EncodedImageInterface::class);
        $encodedTile->shouldReceive('toString')->andReturn('tile-bytes');

        $image = Mockery::mock(ImageInterface::class);
        $image->shouldReceive('width')->andReturn(100);
        $image->shouldReceive('height')->andReturn(100);
        $image->shouldReceive('encode')->andReturn($encodedSource);

        $tile = Mockery::mock(ImageInterface::class);
        $tile->shouldReceive('crop')->andReturnSelf();
        $tile->shouldReceive('encode')->andReturn($encodedTile);

        $imageManager = Mockery::mock(ImageManager::class);
        $imageManager->shouldReceive('decodePath')->once()->with('small-image.png')->andReturn($image);
        $imageManager->shouldReceive('decodeBinary')->once()->andReturn($tile);

        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('makeDirectory')->once()->with('small-folder');
        $disk->shouldReceive('put')->once();
        $disk->shouldReceive('url')->once()->andReturn('https://example.test/small-folder/small-folder_tile_0_0.png');

        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);

        $service = new ImageTilerService($imageManager);

        $tileMap = $service->breakIntoTiles('small-image.png', 'small-folder', 'small-folder');

        $this->assertSame([['https://example.test/small-folder/small-folder_tile_0_0.png']], $tileMap);
    }
}
