<?php

namespace App\Flare\ImageGeneration\DeepAi;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

class DeepAiImageDownloader
{
    public function __construct(private readonly Client $client) {}

    /**
     * Download the generated image bytes from the given remote url.
     *
     *
     * @throws GuzzleException
     */
    public function download(string $url): ?string
    {
        try {
            $response = $this->client->get($url);

            if ($response->getStatusCode() === 200) {
                return $response->getBody()->getContents();
            }

            return null;
        } catch (RequestException $e) {
            throw new Exception($e);
        }
    }
}
