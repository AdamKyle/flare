<?php

namespace Tests\Unit\Flare\ImageGeneration\Services;

use App\Flare\ImageGeneration\DeepAi\DeepAiImageDownloader;
use App\Flare\ImageGeneration\DeepAi\DeepAiImageGeneration;
use App\Flare\ImageGeneration\Services\DeepAiImageTextGenerationService;
use Exception;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class DeepAiImageTextGenerationServiceTest extends TestCase
{
    public function test_generate_image_throws_when_the_api_key_has_not_been_set(): void
    {
        $service = new DeepAiImageTextGenerationService(
            Mockery::mock(DeepAiImageGeneration::class),
            Mockery::mock(DeepAiImageDownloader::class),
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing Api Key. Call setApiKey first.');

        $service->generateImage('A dragon');
    }

    public function test_generate_image_initializes_and_generates_through_deep_ai_image_generation(): void
    {
        $deepAiImageGeneration = Mockery::mock(DeepAiImageGeneration::class);
        $deepAiImageGeneration->shouldReceive('initialize')->once()->with('test-key')->andReturnSelf();
        $deepAiImageGeneration->shouldReceive('generate')->once()->with('A dragon')->andReturn(['output_url' => 'https://api.deepai.org/output/generated.jpg']);

        $service = new DeepAiImageTextGenerationService($deepAiImageGeneration, Mockery::mock(DeepAiImageDownloader::class));

        $result = $service->setApiKey('test-key')->generateImage('A dragon');

        $this->assertSame(['output_url' => 'https://api.deepai.org/output/generated.jpg'], $result);
    }

    public function test_download_and_save_image_returns_false_and_does_not_write_when_download_fails(): void
    {
        Storage::fake('generated-monsters-and-bugs');

        $downloader = Mockery::mock(DeepAiImageDownloader::class);
        $downloader->shouldReceive('download')->once()->with('https://api.deepai.org/output/missing.jpg')->andReturn(null);

        $service = new DeepAiImageTextGenerationService(Mockery::mock(DeepAiImageGeneration::class), $downloader);

        $saved = $service->downloadAndSaveImage('https://api.deepai.org/output/missing.jpg', '/Surface/Goblin.jpg');

        $this->assertFalse($saved);
        Storage::disk('generated-monsters-and-bugs')->assertMissing('/Surface/Goblin.jpg');
    }

    public function test_download_and_save_image_writes_the_file_and_returns_true_when_download_succeeds(): void
    {
        Storage::fake('generated-monsters-and-bugs');

        $downloader = Mockery::mock(DeepAiImageDownloader::class);
        $downloader->shouldReceive('download')->once()->with('https://api.deepai.org/output/generated.jpg')->andReturn('binary-image-bytes');

        $service = new DeepAiImageTextGenerationService(Mockery::mock(DeepAiImageGeneration::class), $downloader);

        $saved = $service->downloadAndSaveImage('https://api.deepai.org/output/generated.jpg', '/Surface/Goblin.jpg');

        $this->assertTrue($saved);
        Storage::disk('generated-monsters-and-bugs')->assertExists('/Surface/Goblin.jpg');
    }

    public function test_image_already_generated_for_monster_delegates_to_storage_exists(): void
    {
        Storage::fake('generated-monsters-and-bugs');
        Storage::disk('generated-monsters-and-bugs')->put('/Surface/Goblin.jpg', 'binary-image-bytes');

        $service = new DeepAiImageTextGenerationService(Mockery::mock(DeepAiImageGeneration::class), Mockery::mock(DeepAiImageDownloader::class));

        $this->assertTrue($service->imageAlreadyGeneratedForMonster('/Surface/Goblin.jpg'));
        $this->assertFalse($service->imageAlreadyGeneratedForMonster('/Surface/Missing.jpg'));
    }
}
