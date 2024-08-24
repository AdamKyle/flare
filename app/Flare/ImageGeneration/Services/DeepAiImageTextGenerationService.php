<?php

namespace App\Flare\ImageGeneration\Services;

use App\Flare\ImageGeneration\DeepAi\DeepAiImageGeneration;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Storage;

class DeepAiImageTextGenerationService {

    private string $apiKey = '';

    public function __construct(private readonly DeepAiImageGeneration $deepAiImageGeneration) {
    }

    public function setApiKey(string $apiKey): DeepAiImageTextGenerationService {
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
     * @param string $url
     * @param string $path
     * @return bool
     * @throws Exception|GuzzleException
     */
    public function downloadAndSaveImage(string $url, string $path): bool {

        $imageClient = new Client();

        try {
            $response = $imageClient->get($url);

            if ($response->getStatusCode() === 200) {

                $imageContents = $response->getBody()->getContents();

                Storage::disk('generated-monsters-and-bugs')->put($path, $imageContents);

                return true;
            }

            return false;
        } catch (RequestException $e) {

            throw new Exception($e);
        }
    }

    public function imageAlreadyGeneratedForMonster(string $path): bool {
        return Storage::disk('generated-monsters-and-bugs')->exists($path);
    }
}
