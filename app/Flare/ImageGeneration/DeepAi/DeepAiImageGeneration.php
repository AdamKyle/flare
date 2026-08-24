<?php

namespace App\Flare\ImageGeneration\DeepAi;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

class DeepAiImageGeneration
{
    private string $apiKey;

    public function __construct(private readonly Client $client) {}

    /**
     * Initialize the class for an api call.
     *
     * @return $this
     */
    public function initialize(string $apiKey): DeepAiImageGeneration
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Generate the image based off the prompt.
     *
     *
     * @throws GuzzleException
     */
    public function generate(string $text): ?array
    {
        try {
            $response = $this->client->post('/api/text2img', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'api-key' => $this->apiKey,
                ],
                'json' => [
                    'text' => $text,
                ],
            ]);

            $body = json_decode($response->getBody(), true);

            return $body;
        } catch (RequestException $e) {
            throw new Exception($e);
        }
    }
}
