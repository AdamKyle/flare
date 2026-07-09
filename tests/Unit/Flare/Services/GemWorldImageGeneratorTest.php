<?php

namespace Tests\Unit\Flare\Services;

use App\Flare\GemWorldGeneration\Services\GemWorldImageGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;

class GemWorldImageGeneratorTest extends TestCase
{
    use CreateGameMap;
    use RefreshDatabase;

    private string $originalMemoryLimit;

    public function setUp(): void
    {
        parent::setUp();

        $this->originalMemoryLimit = ini_get('memory_limit');

        Storage::fake('maps');

        config([
            'gem_world_generation.map_width' => 50,
            'gem_world_generation.map_height' => 50,
        ]);
    }

    public function tearDown(): void
    {
        ini_set('memory_limit', $this->originalMemoryLimit);

        parent::tearDown();
    }

    public function testGeneratorSetsMemoryLimitToConfiguredValue(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);

        $this->app->make(GemWorldImageGenerator::class)->generate($parentMap, 'Test World');

        $this->assertSame('3G', ini_get('memory_limit'));
    }

    public function testGeneratorDoesNotRestoreOldMemoryLimitAfterGeneration(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);

        $safeLowerMemoryLimit = ((int) ceil(memory_get_usage(true) / 1024 / 1024) + 64) . 'M';
        ini_set('memory_limit', $safeLowerMemoryLimit);

        $this->app->make(GemWorldImageGenerator::class)->generate($parentMap, 'Test World');

        $this->assertSame('3G', ini_get('memory_limit'));
    }

    public function testGeneratorDoesNotLowerMemoryBackToThePreviousLowerLimit(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);

        $safeLowerMemoryLimit = ((int) ceil(memory_get_usage(true) / 1024 / 1024) + 64) . 'M';
        ini_set('memory_limit', $safeLowerMemoryLimit);

        $this->app->make(GemWorldImageGenerator::class)->generate($parentMap, 'Test World');

        $this->assertNotSame($safeLowerMemoryLimit, ini_get('memory_limit'));
    }

    public function testShadowPlaneGenerationDoesNotFailAtMemoryRestoreStep(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Shadow Plane']);

        $safeLowerMemoryLimit = ((int) ceil(memory_get_usage(true) / 1024 / 1024) + 64) . 'M';
        ini_set('memory_limit', $safeLowerMemoryLimit);

        $path = $this->app->make(GemWorldImageGenerator::class)->generate($parentMap, 'Shadow Plane World');

        $this->assertIsString($path);
        $this->assertSame('3G', ini_get('memory_limit'));
    }
}
