<?php

namespace Tests\Unit\Flare\ImageGeneration\DeepAi;

use App\Flare\ImageGeneration\DeepAi\DeepAiImageDownloader;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Tests\TestCase;

class DeepAiImageDownloaderTest extends TestCase
{
    public function test_download_returns_the_body_contents_when_status_is_200(): void
    {
        $response = new Response(200, [], 'binary-image-bytes');

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('get')->once()->with('https://api.deepai.org/output/generated.jpg')->andReturn($response);

        $contents = (new DeepAiImageDownloader($client))->download('https://api.deepai.org/output/generated.jpg');

        $this->assertSame('binary-image-bytes', $contents);
    }

    public function test_download_returns_null_when_status_is_not_200(): void
    {
        $response = new Response(404, [], '');

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('get')->once()->andReturn($response);

        $contents = (new DeepAiImageDownloader($client))->download('https://api.deepai.org/output/missing.jpg');

        $this->assertNull($contents);
    }

    public function test_download_throws_when_the_request_fails(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('get')
            ->once()
            ->andThrow(new RequestException('failed', new Request('GET', '/output/generated.jpg')));

        $this->expectException(Exception::class);

        (new DeepAiImageDownloader($client))->download('https://api.deepai.org/output/generated.jpg');
    }
}
