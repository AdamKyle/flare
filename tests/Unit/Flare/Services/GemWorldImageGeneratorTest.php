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

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalMemoryLimit = ini_get('memory_limit');

        Storage::fake('maps');

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

    public function test_generator_sets_memory_limit_to_configured_value(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);

        $this->app->make(GemWorldImageGenerator::class)->generate($parentMap, 'Test World');

        $this->assertSame('3G', ini_get('memory_limit'));
    }

    public function test_generator_does_not_restore_old_memory_limit_after_generation(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);

        $safeLowerMemoryLimit = ((int) ceil(memory_get_usage(true) / 1024 / 1024) + 64).'M';
        ini_set('memory_limit', $safeLowerMemoryLimit);

        $this->app->make(GemWorldImageGenerator::class)->generate($parentMap, 'Test World');

        $this->assertSame('3G', ini_get('memory_limit'));
    }

    public function test_generator_does_not_lower_memory_back_to_the_previous_lower_limit(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);

        $safeLowerMemoryLimit = ((int) ceil(memory_get_usage(true) / 1024 / 1024) + 64).'M';
        ini_set('memory_limit', $safeLowerMemoryLimit);

        $this->app->make(GemWorldImageGenerator::class)->generate($parentMap, 'Test World');

        $this->assertNotSame($safeLowerMemoryLimit, ini_get('memory_limit'));
    }

    public function test_shadow_plane_generation_does_not_fail_at_memory_restore_step(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Shadow Plane']);

        $safeLowerMemoryLimit = ((int) ceil(memory_get_usage(true) / 1024 / 1024) + 64).'M';
        ini_set('memory_limit', $safeLowerMemoryLimit);

        $path = $this->app->make(GemWorldImageGenerator::class)->generate($parentMap, 'Shadow Plane World');

        $this->assertIsString($path);
        $this->assertSame('3G', ini_get('memory_limit'));
    }
}
