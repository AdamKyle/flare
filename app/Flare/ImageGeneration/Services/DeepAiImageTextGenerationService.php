<?php

namespace App\Flare\ImageGeneration\Services;

use App\Flare\ImageGeneration\DeepAi\DeepAiImageDownloader;
use App\Flare\ImageGeneration\DeepAi\DeepAiImageGeneration;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Storage;

class DeepAiImageTextGenerationService
{
    private string $apiKey = '';

    public function __construct(
        private readonly DeepAiImageGeneration $deepAiImageGeneration,
        private readonly DeepAiImageDownloader $deepAiImageDownloader,
    ) {}

    public function setApiKey(string $apiKey): DeepAiImageTextGenerationService
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * @throws Exception|GuzzleException
     */
    public function generateImage(string $prompt): ?array
    {
        if ($this->apiKey === '') {
            throw new Exception('Missing Api Key. Call setApiKey first.');
        }

        return $this->deepAiImageGeneration->initialize($this->apiKey)->generate($prompt);
    }

    /**
     * Download and Save the image.
     *
     *
     * @throws Exception|GuzzleException
     */
    public function downloadAndSaveImage(string $url, string $path): bool
    {
        $imageContents = $this->deepAiImageDownloader->download($url);

        if (is_null($imageContents)) {
            return false;
        }

        Storage::disk('generated-monsters-and-bugs')->put($path, $imageContents);

        return true;
    }

    public function imageAlreadyGeneratedForMonster(string $path): bool
    {
        return Storage::disk('generated-monsters-and-bugs')->exists($path);
    }
}
