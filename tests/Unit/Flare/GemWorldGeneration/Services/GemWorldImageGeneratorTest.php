<?php

namespace Tests\Unit\Flare\GemWorldGeneration\Services;

use App\Flare\MapGenerator\Contracts\LandMapImageFactory;
use App\Flare\MapGenerator\Support\GdPngImageWriter;
use ChristianEssl\LandmapGeneration\Settings\MapSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Setup\GemWorldGeneration\GemWorldImageGeneratorFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;

class GemWorldImageGeneratorTest extends TestCase
{
    use CreateGameMap, RefreshDatabase;

    private string $originalMemoryLimit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalMemoryLimit = ini_get('memory_limit');

        config([
            'gem_world_generation.map_width' => 50,
            'gem_world_generation.map_height' => 50,
        ]);
    }

    protected function tearDown(): void
    {
        ini_set('memory_limit', $this->originalMemoryLimit);

        parent::tearDown();
    }

    public function test_generate_returns_the_computed_gem_world_path(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);

        $path = (new GemWorldImageGeneratorFactory())->build()->generate($parentMap, 'Test World');

        $this->assertSame('generated-gem-worlds/test-world.png', $path);
    }

    public function test_generate_builds_the_image_via_the_land_map_image_factory_using_the_configured_dimensions(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);

        $capturedSettings = null;

        $landMapImageFactory = Mockery::mock(LandMapImageFactory::class);
        $landMapImageFactory->shouldReceive('build')
            ->once()
            ->with(Mockery::on(function (MapSettings $settings) use (&$capturedSettings) {
                $capturedSettings = $settings;

                return true;
            }), 'test-world')
            ->andReturn('fake-image-resource');

        (new GemWorldImageGeneratorFactory())->build(landMapImageFactory: $landMapImageFactory)->generate($parentMap, 'Test World');

        $this->assertSame(50, $capturedSettings->getWidth());
        $this->assertSame(50, $capturedSettings->getHeight());
    }

    public function test_generate_stores_the_built_image_on_the_maps_disk(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);

        $landMapImageFactory = Mockery::mock(LandMapImageFactory::class);
        $landMapImageFactory->shouldReceive('build')->once()->andReturn('fake-image-resource');

        $imageWriter = Mockery::mock(GdPngImageWriter::class);
        $imageWriter->shouldReceive('encodeAndStore')
            ->once()
            ->with('fake-image-resource', 'maps', 'generated-gem-worlds/test-world.png');

        $path = (new GemWorldImageGeneratorFactory())->build(landMapImageFactory: $landMapImageFactory, imageWriter: $imageWriter)->generate($parentMap, 'Test World');

        $this->assertSame('generated-gem-worlds/test-world.png', $path);
    }

    public function test_generator_sets_memory_limit_to_configured_value(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);

        (new GemWorldImageGeneratorFactory())->build()->generate($parentMap, 'Test World');

        $this->assertSame('3G', ini_get('memory_limit'));
    }

    public function test_generator_does_not_restore_old_memory_limit_after_generation(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);

        $safeLowerMemoryLimit = ((int) ceil(memory_get_usage(true) / 1024 / 1024) + 64).'M';
        ini_set('memory_limit', $safeLowerMemoryLimit);

        (new GemWorldImageGeneratorFactory())->build()->generate($parentMap, 'Test World');

        $this->assertSame('3G', ini_get('memory_limit'));
    }
}
