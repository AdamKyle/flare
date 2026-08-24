<?php

namespace Tests\Unit\Flare\ImageGeneration\DeepAi;

use App\Flare\ImageGeneration\DeepAi\DeepAiImageGeneration;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Tests\TestCase;

class DeepAiImageGenerationTest extends TestCase
{
    public function test_generate_posts_the_prompt_and_returns_the_decoded_response(): void
    {
        $response = new Response(200, [], json_encode(['output_url' => 'https://api.deepai.org/output/generated.jpg']));

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('post')
            ->once()
            ->with('/api/text2img', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'api-key' => 'test-api-key',
                ],
                'json' => [
                    'text' => 'A dragon',
                ],
            ])
            ->andReturn($response);

        $result = (new DeepAiImageGeneration($client))->initialize('test-api-key')->generate('A dragon');

        $this->assertSame(['output_url' => 'https://api.deepai.org/output/generated.jpg'], $result);
    }

    public function test_generate_throws_when_the_request_fails(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('post')
            ->once()
            ->andThrow(new RequestException('failed', new Request('POST', '/api/text2img')));

        $this->expectException(Exception::class);

        (new DeepAiImageGeneration($client))->initialize('test-api-key')->generate('A dragon');
    }
}
