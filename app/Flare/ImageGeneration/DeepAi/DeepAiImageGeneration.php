<?php

namespace App\Flare\ImageGeneration\DeepAi;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;use GuzzleHttp\Exception\RequestException;

class DeepAiImageGeneration
{
    private Client $client;
    private string $apiKey;

    /**
     * Initialize the class for an api call.
     *
     * @param string $apiKey
     * @return $this
     */
    public function initialize(string $apiKey): DeepAiImageGeneration {
        $this->apiKey = $apiKey;

        $this->client = new Client([
            'base_uri' => 'https://api.deepai.org',
        ]);

        return $this;
    }

    /**
     * Generate the image based off the prompt.
     *
     * @param string $text
     * @return array|null
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

