<?php

namespace Tests\Feature\Flare\ImageGeneration\Commands\Console;

use App\Flare\ImageGeneration\Services\DeepAiImageTextGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateMonster;

class GenerateMonsterImagesTest extends TestCase
{
    use CreateGameMap, CreateMonster, RefreshDatabase;

    protected function tearDown(): void
    {
        putenv('DEEP_AI_API_KEY');
        unset($_ENV['DEEP_AI_API_KEY']);

        parent::tearDown();
    }

    public function test_missing_game_map_name_reports_error(): void
    {
        $this->artisan('generate:monster-images', ['gameMapName' => ''])
            ->expectsOutputToContain('Missing game Map Name')
            ->assertExitCode(0);
    }

    public function test_missing_api_key_reports_error(): void
    {
        putenv('DEEP_AI_API_KEY');
        unset($_ENV['DEEP_AI_API_KEY']);

        $gameMap = $this->createGameMap();

        $this->artisan('generate:monster-images', ['gameMapName' => $gameMap->name])
            ->expectsOutputToContain('Missing APi Key for Deep AI')
            ->assertExitCode(0);
    }

    public function test_unknown_game_map_reports_error(): void
    {
        putenv('DEEP_AI_API_KEY=test-key');

        $this->artisan('generate:monster-images', ['gameMapName' => 'Not A Real Map'])
            ->expectsOutputToContain('Unknown game map for name: Not A Real Map')
            ->assertExitCode(0);
    }

    public function test_skips_monster_that_already_has_a_generated_image(): void
    {
        putenv('DEEP_AI_API_KEY=test-key');

        $gameMap = $this->createGameMap(['name' => 'Surface']);
        $this->createMonster(['name' => 'Goblin', 'game_map_id' => $gameMap->id]);

        $service = Mockery::mock(DeepAiImageTextGenerationService::class);
        $service->shouldReceive('imageAlreadyGeneratedForMonster')->once()->with('/Surface/Goblin.jpg')->andReturn(true);
        $service->shouldNotReceive('setApiKey');
        $service->shouldNotReceive('generateImage');

        $this->app->instance(DeepAiImageTextGenerationService::class, $service);

        $this->artisan('generate:monster-images', ['gameMapName' => 'Surface'])->assertExitCode(0);
    }

    public function test_generates_and_saves_the_image_for_a_monster(): void
    {
        putenv('DEEP_AI_API_KEY=test-key');

        $gameMap = $this->createGameMap(['name' => 'Surface']);
        $this->createMonster(['name' => 'Goblin', 'game_map_id' => $gameMap->id]);

        $service = Mockery::mock(DeepAiImageTextGenerationService::class);
        $service->shouldReceive('imageAlreadyGeneratedForMonster')->once()->with('/Surface/Goblin.jpg')->andReturn(false);
        $service->shouldReceive('setApiKey')->once()->with('test-key')->andReturnSelf();
        $service->shouldReceive('generateImage')
            ->once()
            ->with('Generate an Epic High Fantasy: Goblin without any additional text, just the image.')
            ->andReturn(['output_url' => 'https://api.deepai.org/output/generated.jpg']);
        $service->shouldReceive('downloadAndSaveImage')->once()->with('https://api.deepai.org/output/generated.jpg', '/Surface/Goblin.jpg')->andReturn(true);

        $this->app->instance(DeepAiImageTextGenerationService::class, $service);

        $this->artisan('generate:monster-images', ['gameMapName' => 'Surface'])
            ->expectsOutputToContain('Saved image for monster: Goblin who belongs to game map: Surface')
            ->assertExitCode(0);
    }

    public function test_reports_error_when_generation_response_is_empty(): void
    {
        putenv('DEEP_AI_API_KEY=test-key');

        $gameMap = $this->createGameMap(['name' => 'Surface']);
        $this->createMonster(['name' => 'Goblin', 'game_map_id' => $gameMap->id]);

        $service = Mockery::mock(DeepAiImageTextGenerationService::class);
        $service->shouldReceive('imageAlreadyGeneratedForMonster')->once()->andReturn(false);
        $service->shouldReceive('setApiKey')->once()->andReturnSelf();
        $service->shouldReceive('generateImage')->once()->andReturn(null);
        $service->shouldNotReceive('downloadAndSaveImage');

        $this->app->instance(DeepAiImageTextGenerationService::class, $service);

        $this->artisan('generate:monster-images', ['gameMapName' => 'Surface'])
            ->expectsOutputToContain('Something went wrong saving the image')
            ->assertExitCode(0);
    }

    public function test_reports_error_when_saving_the_downloaded_image_fails(): void
    {
        putenv('DEEP_AI_API_KEY=test-key');

        $gameMap = $this->createGameMap(['name' => 'Surface']);
        $this->createMonster(['name' => 'Goblin', 'game_map_id' => $gameMap->id]);

        $service = Mockery::mock(DeepAiImageTextGenerationService::class);
        $service->shouldReceive('imageAlreadyGeneratedForMonster')->once()->andReturn(false);
        $service->shouldReceive('setApiKey')->once()->andReturnSelf();
        $service->shouldReceive('generateImage')->once()->andReturn(['output_url' => 'https://api.deepai.org/output/generated.jpg']);
        $service->shouldReceive('downloadAndSaveImage')->once()->andReturn(false);

        $this->app->instance(DeepAiImageTextGenerationService::class, $service);

        $this->artisan('generate:monster-images', ['gameMapName' => 'Surface'])
            ->expectsOutputToContain('Something went wrong saving the image')
            ->assertExitCode(0);
    }
}
